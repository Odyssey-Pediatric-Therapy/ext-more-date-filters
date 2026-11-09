<?php

namespace tests\unit\Espo\Modules\MoreDateFilters;

use DateTime;
use DateTimeZone;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Core\Select\Where\Item;
use Espo\Modules\MoreDateFilters\Services\Core;
use Espo\Modules\MoreDateFilters\Select\Where\RangeConverter;
use Espo\ORM\Query\SelectBuilder;
use PHPUnit\Framework\TestCase;

class ConverterTest extends TestCase
{
    private function converter(string $timezone = 'UTC'): RangeConverter
    {
        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnCallback(fn ($key, $default = null) => ['timeZone' => $timezone, 'weekStart' => 1][$key] ?? $default);
        $metadata = $this->createMock(Metadata::class);
        $defs = [
            'Contact' => ['fields' => [
                'birthday' => ['type' => 'date'],
                'createdAt' => ['type' => 'datetime'],
                'dateStart' => ['type' => 'datetimeOptional'],
                'foreignBirthday' => ['type' => 'foreign', 'link' => 'account', 'field' => 'birthday'],
                'foreignStart' => ['type' => 'foreign', 'link' => 'account', 'field' => 'dateStart'],
                'cycle' => ['type' => 'foreign', 'link' => 'contact', 'field' => 'cycle'],
                'name' => ['type' => 'varchar'],
            ], 'links' => ['account' => ['entity' => 'Account'], 'contact' => ['entity' => 'Contact']]],
            'Account' => ['fields' => ['birthday' => ['type' => 'date'], 'dateStart' => ['type' => 'datetimeOptional']]],
        ];
        $metadata->method('get')->willReturnCallback(static function ($path, $default = null) use ($defs) {
            $node = ['entityDefs' => $defs];
            foreach ($path as $key) {
                if (!is_array($node) || !array_key_exists($key, $node)) return $default;
                $node = $node[$key];
            }
            return $node;
        });
        $core = $this->getMockBuilder(Core::class)->onlyMethods(['now'])->getMock();
        $core->method('now')->willReturnCallback(fn ($zone) => (new DateTime('2025-09-09 23:30:00', new DateTimeZone('UTC')))->setTimezone($zone));
        return new RangeConverter('Contact', $config, $core, $metadata);
    }

    private function item(string $attribute, string $type = 'lastXDaysExt', mixed $value = 2, ?string $timezone = null): Item
    {
        return Item::fromRaw(['type' => $type, 'attribute' => $attribute, 'value' => $value, 'date' => true, 'timeZone' => $timezone]);
    }

    public function testRequestTimezoneAndForeignDateResolution(): void
    {
        foreach (['birthday', 'foreignBirthday', 'account.birthday'] as $attribute) {
            $where = $this->converter('America/Denver')->convert(SelectBuilder::create(), $this->item($attribute, 'yesterdayExt', null, 'Asia/Tokyo'))->getRaw();
            $this->assertSame(['AND' => [$attribute . '>=' => '2025-09-09', $attribute . '<' => '2025-09-10']], $where);
        }
    }

    public function testDatetimeUsesRequestTimezoneAndUtcBoundaries(): void
    {
        $where = $this->converter()->convert(SelectBuilder::create(), $this->item('createdAt', 'yesterdayExt', null, 'Asia/Tokyo'))->getRaw();
        $this->assertSame(['AND' => ['createdAt>=' => '2025-09-08 15:00:00', 'createdAt<' => '2025-09-09 15:00:00']], $where);
    }

    public function testConfiguredWeekStart(): void
    {
        $where = $this->converter()->convert(SelectBuilder::create(), $this->item('birthday', 'currentWeekExt'))->getRaw();
        $this->assertSame(['AND' => ['birthday>=' => '2025-09-08', 'birthday<' => '2025-09-15']], $where);
    }

    public function testOptionalDatetimeIncludesAllDayAndTimedValues(): void
    {
        $where = $this->converter()->convert(SelectBuilder::create(), $this->item('dateStart'))->getRaw();
        $this->assertSame(['OR' => [
            ['AND' => ['dateStartDate>=' => '2025-09-07', 'dateStartDate<' => '2025-09-09']],
            ['AND' => [
                ['AND' => ['dateStart>=' => '2025-09-07 00:00:00', 'dateStart<' => '2025-09-09 00:00:00']],
                ['dateStartDate' => null],
            ]],
        ]], $where);
    }

    public function testOptionalDatetimeSubDayFiltersExcludeAllDayValues(): void
    {
        $where = $this->converter()->convert(SelectBuilder::create(), $this->item('dateStart', 'lastXMinutesExt', 3))->getRaw();
        $this->assertSame(['AND' => [
            ['AND' => ['dateStart>=' => '2025-09-09 23:27:00', 'dateStart<' => '2025-09-09 23:30:00']],
            ['dateStartDate' => null],
        ]], $where);
    }

    public function testForeignOptionalCompanionUsesRelatedAttribute(): void
    {
        $where = $this->converter()->convert(SelectBuilder::create(), $this->item('foreignStart'))->getRaw();
        $this->assertArrayHasKey('account.dateStartDate>=', $where['OR'][0]['AND']);
    }

    /** @dataProvider zeroOffsetFilters */
    public function testZeroWeekOffsetsAreAccepted(string $type, array $expected): void
    {
        foreach ([0, '0'] as $value) {
            $where = $this->converter()->convert(SelectBuilder::create(), $this->item('createdAt', $type, $value))->getRaw();
            $this->assertSame(['AND' => $expected], $where);
        }
    }

    public static function zeroOffsetFilters(): array
    {
        // The configured week starts Monday; the fixed clock is Tuesday September 9.
        return [
            ['upToNextXWeeksExt', ['createdAt<' => '2025-09-15 00:00:00']],
            ['upToNextXWeeksFutureExt', ['createdAt>=' => '2025-09-09 23:30:00', 'createdAt<' => '2025-09-15 00:00:00']],
            ['afterNextXWeeksExt', ['createdAt>=' => '2025-09-15 00:00:00']],
            ['beforeXWeeksAgoExt', ['createdAt<' => '2025-09-08 00:00:00']],
            ['afterXWeeksAgoExt', ['createdAt>=' => '2025-09-15 00:00:00']],
        ];
    }

    /** @dataProvider zeroLengthFilters */
    public function testZeroIsStillRejectedForEmptyRanges(string $type): void
    {
        $this->expectException(BadRequest::class);
        $this->converter()->convert(SelectBuilder::create(), $this->item('createdAt', $type, 0));
    }

    public static function zeroLengthFilters(): array
    {
        return [['lastXWeeksExt'], ['nextXWeeksExt'], ['lastXHoursExt'], ['nextXMinutesExt'], ['afterXWeeksAgoPastExt']];
    }

    /** @dataProvider invalidCounts */
    public function testInvalidCountIsRejected(mixed $count): void
    {
        $this->expectException(BadRequest::class);
        $this->converter()->convert(SelectBuilder::create(), $this->item('createdAt', 'lastXMinutesExt', $count));
    }

    public static function invalidCounts(): array
    {
        return array_map(fn ($value) => [$value], [null, 0, -1, '', 'abc', '1.5', 1.5, true, [], '01', ' 2', '10001', '999999999999999999999']);
    }

    /** @dataProvider invalidFields */
    public function testInvalidFieldIsRejected(string $attribute): void
    {
        $this->expectException(BadRequest::class);
        $this->converter()->convert(SelectBuilder::create(), $this->item($attribute));
    }

    public static function invalidFields(): array
    {
        return [['missing'], ['name'], ['missing.birthday'], ['cycle']];
    }

    public function testInvalidTimezoneIsRejected(): void
    {
        $this->expectException(BadRequest::class);
        $this->converter()->convert(SelectBuilder::create(), $this->item('createdAt', 'lastXHoursExt', 1, 'invalid/timezone'));
    }

    public function testOutOfCalendarRangeIsRejected(): void
    {
        $this->expectException(BadRequest::class);
        $this->converter()->convert(SelectBuilder::create(), $this->item('birthday', 'nextXYearsExt', 10000));
    }

    public function testEveryRegisteredConverterLoadsAndProducesAValidRange(): void
    {
        $path = dirname(__DIR__, 5) . '/src/files/custom/Espo/Modules/MoreDateFilters/Resources/metadata/app/select.json';
        $map = json_decode(file_get_contents($path), true)['whereItemConverterClassNameMap'];
        foreach ($map as $type => $class) {
            $this->assertTrue(is_a($class, RangeConverter::class, true), $type);
            $where = $this->converter()->convert(SelectBuilder::create(), $this->item('createdAt', $type))->getRaw()['AND'];
            $this->assertNotEmpty($where, $type);
            if (count($where) === 2) $this->assertLessThanOrEqual($where['createdAt<'], $where['createdAt>='], $type);
        }
    }
}
