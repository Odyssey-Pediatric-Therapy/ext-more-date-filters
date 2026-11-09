<?php

declare(strict_types=1);

namespace tests\unit\Espo\Modules\MoreDateFilters;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\DateFilterSeed;

require_once dirname(__DIR__, 4) . '/fixtures/DateFilterSeed.php';

class SeedTimeWindowTest extends TestCase
{
    /** @dataProvider timeWindows */
    public function testOrdinaryEventsStartAndEndInsideTheLocalWindow(string $zone, string $start, string $end, string $expectedStart, string $expectedEnd): void
    {
        $timezone = new DateTimeZone($zone);
        $now = new DateTimeImmutable('2026-11-09 12:22:22', $timezone);
        $faker = \Faker\Factory::create('en_US');
        $faker->seed(725);
        $plan = new DateFilterSeed($now, 'time-window', faker: $faker, startTime: $start, endTime: $end);
        $this->assertSame($expectedStart, $plan->startTime);
        $this->assertSame($expectedEnd, $plan->endTime);
        $counts = $offsets = $violations = [];
        foreach ($plan->records() as $record) {
            $row = $record['attributes'];
            if (!str_contains($row['description'], 'Scenario: spread.')) continue;
            $type = $record['entityType'];
            $counts[$type] = ($counts[$type] ?? 0) + 1;
            $from = (new DateTimeImmutable($row['dateStart'], new DateTimeZone('UTC')))->setTimezone($timezone);
            $to = (new DateTimeImmutable($row['dateEnd'], new DateTimeZone('UTC')))->setTimezone($timezone);
            $offsets[$from->format('P')] = true;
            if ($from->format('H:i:s') < $expectedStart . ':00' || $to->format('H:i:s') > $expectedEnd . ':00' ||
                $from >= $to || $from->format('Y-m-d') !== $to->format('Y-m-d')) {
                $violations[] = "$type event outside the local window";
            }
        }
        $this->assertSame([], $violations);
        foreach (['Task', 'Meeting', 'Call'] as $type) $this->assertGreaterThan(1900, $counts[$type]);
        if ($zone === 'America/Denver') {
            $this->assertArrayHasKey('-06:00', $offsets, 'Summer uses the local daylight offset.');
            $this->assertArrayHasKey('-07:00', $offsets, 'Winter uses the local standard offset.');
        }
    }

    public static function timeWindows(): array
    {
        return [
            ['America/Denver', '0800', '1800', '08:00', '18:00'],
            ['Asia/Kathmandu', '09:30', '16:30', '09:30', '16:30'],
            ['America/Denver', '0800', '08:01', '08:00', '08:01'],
            ['America/Denver', '0210', '0240', '02:10', '02:40'],
        ];
    }

    public function testDefaultsAreEightAmToSixPm(): void
    {
        $plan = new DateFilterSeed(new DateTimeImmutable(), 'defaults');
        $this->assertSame('08:00', $plan->startTime);
        $this->assertSame('18:00', $plan->endTime);
    }

    /** @dataProvider invalidTimes */
    public function testInvalidOrReversedTimesAreRejected(string $start, string $end): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DateFilterSeed(new DateTimeImmutable(), 'invalid-window', startTime: $start, endTime: $end);
    }

    public static function invalidTimes(): array
    {
        return [['800', '1800'], ['08:60', '1800'], ['0800', '2400'], ['0800', '08:00'], ['1800', '0800'], ['text', '1800']];
    }
}
