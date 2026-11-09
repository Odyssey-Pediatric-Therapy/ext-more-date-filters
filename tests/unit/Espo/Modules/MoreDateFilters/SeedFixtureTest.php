<?php

declare(strict_types=1);

namespace tests\unit\Espo\Modules\MoreDateFilters;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Espo\Modules\MoreDateFilters\Services\Core;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\DateFilterSeed;

require_once dirname(__DIR__, 4) . '/fixtures/DateFilterSeed.php';

class SeedFixtureTest extends TestCase
{
    public function testFixturesSpanFourYearsAndPopulateEveryExtensionRangeOnBothEventDates(): void
    {
        $now = new DateTimeImmutable('2025-09-09 12:22:22', new DateTimeZone('America/Denver'));
        $plan = new DateFilterSeed($now, 'coverage');
        $values = $counts = [];
        $allDay = $undated = 0;
        $violations = [];
        foreach ($plan->records() as $record) {
            $type = $record['entityType'];
            $attributes = $record['attributes'];
            $counts[$type] = ($counts[$type] ?? 0) + 1;
            if ($attributes['name'] === '' || str_contains($attributes['name'], 'MDF-TEST-')) $violations[] = 'Non-human title';
            foreach (['dateStart', 'dateEnd', 'createdAt', 'modifiedAt'] as $field) {
                if ($attributes[$field] === null) continue;
                $timestamp = (new DateTimeImmutable($attributes[$field], new DateTimeZone('UTC')))->getTimestamp();
                if ($timestamp < $plan->from->getTimestamp() || $timestamp > $plan->to->getTimestamp()) $violations[] = "$type $field out of range";
                $values[$type][$field][] = $timestamp;
            }
            if ($attributes['dateStart'] !== null && $attributes['dateStart'] >= $attributes['dateEnd']) $violations[] = 'Invalid duration';
            if ($attributes['dateStart'] === null) $undated++;
            if (!empty($attributes['dateStartDate'])) $allDay++;
            if ($type === 'Call' && (!$attributes['dateStart'] || !$attributes['dateEnd'])) $violations[] = 'Undated call';
            if ($type === 'Task' && $attributes['dateCompleted'] !== null &&
                new DateTimeImmutable($attributes['dateCompleted'], new DateTimeZone('UTC')) > $now) $violations[] = 'Future completion';
        }
        $this->assertSame([], $violations);
        $this->assertSame(20, $undated);
        $this->assertGreaterThan(0, $allDay);
        foreach ($counts as $type => $count) {
            $this->assertGreaterThanOrEqual(2000, $count, $type);
            $this->assertSame($plan->from->getTimestamp(), min($values[$type]['dateStart']));
            $this->assertSame($plan->to->getTimestamp(), max($values[$type]['dateEnd']));
            $this->assertSame($plan->from->getTimestamp(), min($values[$type]['createdAt']));
            $this->assertSame($plan->to->getTimestamp(), max($values[$type]['createdAt']));
        }
        $metadata = dirname(__DIR__, 5) . '/src/files/custom/Espo/Modules/MoreDateFilters/Resources/metadata/app/select.json';
        $filters = array_keys(json_decode(file_get_contents($metadata), true)['whereItemConverterClassNameMap']);
        foreach ($filters as $filter) {
            foreach (str_contains($filter, 'X') ? [1, 2] : [1] as $x) {
                [$from, $to] = (new Core())->range($filter, DateTime::createFromImmutable($now), $x);
                foreach (['Task', 'Meeting', 'Call'] as $type) {
                    foreach (['dateStart', 'dateEnd', 'createdAt', 'modifiedAt'] as $field) {
                        $matches = array_filter($values[$type][$field], fn ($stamp) =>
                            (!$from || $stamp >= $from->getTimestamp()) && (!$to || $stamp < $to->getTimestamp()));
                        $this->assertNotEmpty($matches, "$type.$field must match $filter, X=$x");
                    }
                }
            }
        }
    }

    public function testLeapDayUsesClampedCalendarYearsAndHonorsRequestedCount(): void
    {
        $plan = new DateFilterSeed(new DateTimeImmutable('2024-02-29 12:34:56', new DateTimeZone('Asia/Tokyo')), 'leap', 2100, 1);
        $this->assertSame('2022-02-28 12:34:56', $plan->from->format('Y-m-d H:i:s'));
        $this->assertSame('2026-02-28 12:34:56', $plan->to->format('Y-m-d H:i:s'));
        $counts = [];
        foreach ($plan->records() as $record) $counts[$record['entityType']] = ($counts[$record['entityType']] ?? 0) + 1;
        foreach ($counts as $count) $this->assertGreaterThanOrEqual(2100, $count);
    }

    public function testRandomizedCountsDatesAndTitlesWithLowAllDayShare(): void
    {
        $snapshot = static function (int $seed): array {
            $faker = \Faker\Factory::create('en_US');
            $faker->seed($seed);
            $plan = new DateFilterSeed(new DateTimeImmutable('2025-09-09 12:22:22', new DateTimeZone('UTC')), 'random-check', faker: $faker);
            $counts = $allDay = $samples = [];
            foreach ($plan->records() as $record) {
                $type = $record['entityType'];
                $row = $record['attributes'];
                $counts[$type] = ($counts[$type] ?? 0) + 1;
                if (!empty($row['dateStartDate'])) $allDay[$type] = ($allDay[$type] ?? 0) + 1;
                if (count($samples[$type] ?? []) < 5) $samples[$type][] = [$row['name'], $row['dateStart']];
            }
            return [$counts, $allDay, $samples];
        };
        [$counts, $allDay, $samples] = $snapshot(11);
        [$otherCounts, , $otherSamples] = $snapshot(29);
        $this->assertNotSame($counts, $otherCounts, 'Counts must vary between runs.');
        foreach (['Task', 'Meeting', 'Call'] as $type) {
            $this->assertGreaterThanOrEqual(2000, $counts[$type]);
            $this->assertNotSame(array_column($samples[$type], 0), array_column($otherSamples[$type], 0), 'Titles must vary.');
            $this->assertNotSame(array_column($samples[$type], 1), array_column($otherSamples[$type], 1), 'Spread dates must vary.');
        }
        foreach (['Task', 'Meeting'] as $type) {
            $this->assertGreaterThan(0.02, $allDay[$type] / $counts[$type]);
            $this->assertLessThan(0.04, $allDay[$type] / $counts[$type]);
        }
        $this->assertArrayNotHasKey('Call', $allDay);
    }

    public function testAllDayFixturesCanBeDisabled(): void
    {
        $plan = new DateFilterSeed(new DateTimeImmutable('2025-09-09 12:22:22'), 'timed-only', allDayPercent: 0);
        $allDay = 0;
        foreach ($plan->records() as $record) {
            if (!empty($record['attributes']['dateStartDate'])) $allDay++;
        }
        $this->assertSame(0, $allDay);
    }

    /** @dataProvider daylightSavingZones */
    public function testExplicitFixturesSurroundEveryTimezoneTransition(string $zone): void
    {
        $timezone = new DateTimeZone($zone);
        $plan = new DateFilterSeed(new DateTimeImmutable('2026-11-09 12:22:22', $timezone), 'dst-check');
        $values = $intervals = $allDay = [];
        foreach ($plan->records() as $record) {
            $type = $record['entityType'];
            $row = $record['attributes'];
            if (!empty($row['dateStartDate'])) {
                $allDay[$type][$row['dateStartDate']] =
                    (new DateTimeImmutable($row['dateEnd'], new DateTimeZone('UTC')))->getTimestamp() -
                    (new DateTimeImmutable($row['dateStart'], new DateTimeZone('UTC')))->getTimestamp();
                continue;
            }
            if ($row['dateStart'] === null) continue;
            $start = (new DateTimeImmutable($row['dateStart'], new DateTimeZone('UTC')))->getTimestamp();
            $end = (new DateTimeImmutable($row['dateEnd'], new DateTimeZone('UTC')))->getTimestamp();
            $values[$type]['dateStart'][$start] = true;
            $values[$type]['dateEnd'][$end] = true;
            $intervals[$type][] = [$start, $end];
        }
        $previous = null;
        $directions = [];
        foreach ($timezone->getTransitions($plan->from->getTimestamp(), $plan->to->getTimestamp()) as $transition) {
            if ($previous !== null && $transition['offset'] !== $previous) {
                $timestamp = $transition['ts'];
                $delta = $transition['offset'] - $previous;
                $directions[$delta > 0 ? 'forward' : 'backward'] = true;
                $half = intdiv(abs($delta), 2);
                foreach (['Task', 'Meeting', 'Call'] as $type) {
                    foreach (['dateStart', 'dateEnd'] as $field) {
                        foreach ([-1, 0, 1, -$half, $half] as $seconds) {
                            $this->assertArrayHasKey($timestamp + $seconds, $values[$type][$field], "$zone $type.$field at transition $timestamp offset $seconds");
                        }
                    }
                    $crossing = array_filter($intervals[$type], fn ($interval) => $interval[0] < $timestamp && $interval[1] > $timestamp);
                    $this->assertNotEmpty($crossing, "$type must have an event spanning the transition.");
                }
                $day = (new DateTimeImmutable('@' . $timestamp))->setTimezone($timezone)->format('Y-m-d');
                foreach (['Task', 'Meeting'] as $type) {
                    $this->assertArrayHasKey($day, $allDay[$type]);
                    $this->assertSame(86400 - $delta, $allDay[$type][$day], 'All-day duration must follow the actual local day length.');
                }
                if ($delta < 0) {
                    $first = (new DateTimeImmutable('@' . ($timestamp - $half)))->setTimezone($timezone);
                    $second = (new DateTimeImmutable('@' . ($timestamp + $half)))->setTimezone($timezone);
                    $this->assertSame($first->format('Y-m-d H:i:s'), $second->format('Y-m-d H:i:s'), 'Both occurrences of the repeated local time must be represented.');
                    $this->assertNotSame($first->format('P'), $second->format('P'));
                }
            }
            $previous = $transition['offset'];
        }
        $this->assertArrayHasKey('forward', $directions);
        $this->assertArrayHasKey('backward', $directions);
    }

    public static function daylightSavingZones(): array
    {
        return [['America/Denver'], ['Australia/Lord_Howe']];
    }

    public function testLeapDayAndAdjacentMidnightsAreExplicitFixtures(): void
    {
        $timezone = new DateTimeZone('Asia/Tokyo');
        $plan = new DateFilterSeed(new DateTimeImmutable('2026-11-09 12:22:22', $timezone), 'leap-day-check');
        $values = $allDay = [];
        foreach ($plan->records() as $record) {
            $type = $record['entityType'];
            $row = $record['attributes'];
            if (!empty($row['dateStartDate'])) $allDay[$type][$row['dateStartDate']] = true;
            foreach (['dateStart', 'dateEnd'] as $field) {
                if ($row[$field] === null) continue;
                $local = (new DateTimeImmutable($row[$field], new DateTimeZone('UTC')))->setTimezone($timezone)->format('Y-m-d H:i:s');
                $values[$type][$field][$local] = true;
            }
        }
        foreach (['Task', 'Meeting', 'Call'] as $type) {
            foreach (['dateStart', 'dateEnd'] as $field) {
                foreach (['2028-02-28 23:59:59', '2028-02-29 00:00:00', '2028-02-29 00:00:01',
                    '2028-02-29 12:00:00', '2028-02-29 23:59:59', '2028-03-01 00:00:00', '2028-03-01 00:00:01'] as $date) {
                    $this->assertArrayHasKey($date, $values[$type][$field], "$type.$field needs $date");
                }
            }
        }
        foreach (['Task', 'Meeting'] as $type) $this->assertArrayHasKey('2028-02-29', $allDay[$type]);
    }

    public function testNoDstOrLeapDayFixturesAreInventedForUtcOrNonLeapCentury(): void
    {
        // 2100 is divisible by 100 but not 400, so this window has no February 29.
        $plan = new DateFilterSeed(new DateTimeImmutable('2100-07-01 12:22:22', new DateTimeZone('UTC')), 'no-transitions');
        $special = [];
        foreach ($plan->records() as $record) {
            $description = $record['attributes']['description'];
            if (str_contains($description, 'dst:') || str_contains($description, 'leap-day:')) $special[] = $description;
        }
        $this->assertSame([], $special);
    }

    public function testRecordsStaySpreadOutRatherThanClusteringOnToday(): void
    {
        $zone = new DateTimeZone('America/Denver');
        $now = new DateTimeImmutable('2026-11-09 12:22:22', $zone);
        $days = [];
        $faker = \Faker\Factory::create('en_US');
        $faker->seed(1234);
        foreach ((new DateFilterSeed($now, 'daily-spread', faker: $faker))->records() as $record) {
            foreach (['dateStart', 'dateEnd', 'createdAt', 'modifiedAt'] as $field) {
                $value = $record['attributes'][$field];
                if ($value === null) continue;
                $day = (new DateTimeImmutable($value, new DateTimeZone('UTC')))->setTimezone($zone)->format('Y-m-d');
                $type = $record['entityType'];
                $days[$type][$field][$day] = ($days[$type][$field][$day] ?? 0) + 1;
            }
        }
        foreach ($days as $type => $fields) {
            foreach ($fields as $field => $counts) {
                $this->assertLessThanOrEqual(8, $counts[$now->format('Y-m-d')] ?? 0, "$type.$field on today");
                $this->assertLessThanOrEqual(12, max($counts), "$type.$field on any day, including DST fixtures");
                $this->assertGreaterThan(1000, count($counts), "$type.$field must span most of the four-year window");
                $ordinary = array_filter($counts, fn ($count) => $count <= 3);
                $this->assertGreaterThan(0.95, count($ordinary) / count($counts), "$type.$field needs only a few records on ordinary days");
            }
        }
    }

    public function testCannotRequestFewerThanTwoThousandRecords(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new DateFilterSeed(new DateTimeImmutable(), 'too-few', 1999);
    }
}
