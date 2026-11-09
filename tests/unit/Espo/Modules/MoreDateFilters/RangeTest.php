<?php

namespace tests\unit\Espo\Modules\MoreDateFilters;

use DateTime;
use DateTimeZone;
use Espo\Core\Exceptions\BadRequest;
use Espo\Modules\MoreDateFilters\Services\Core;
use PHPUnit\Framework\TestCase;

class RangeTest extends TestCase
{
    /** @dataProvider ranges */
    public function testRange(string $type, string $anchor, string $tz, int $x, bool $dateOnly, int $weekStart, ?string $from, ?string $to): void
    {
        $now = new DateTime($anchor, new DateTimeZone($tz));
        $original = $now->format('c');
        $result = (new Core())->range($type, $now, $x, $weekStart, $dateOnly);
        $format = $dateOnly ? 'Y-m-d' : 'Y-m-d H:i:s';
        $this->assertSame([$from, $to], array_map(fn ($date) => $date?->format($format), $result));
        $this->assertSame($original, $now->format('c'), 'The input clock must not be mutated.');
    }

    public static function ranges(): array
    {
        return [
            ['nextXMonthsExt','2025-01-31','UTC',1,true,0,'2025-02-01','2025-03-01'],
            ['lastXMonthsExt','2025-03-31','UTC',1,true,0,'2025-02-01','2025-03-01'],
            ['nextQuarterExt','2025-03-31','UTC',1,true,0,'2025-04-01','2025-07-01'],
            ['lastXQuartersExt','2025-12-31','UTC',1,true,0,'2025-07-01','2025-10-01'],
            ['nextXQuartersExt','2025-03-31','UTC',2,true,0,'2025-04-01','2025-10-01'],
            ['lastXYearsExt','2024-02-29','UTC',1,true,0,'2023-01-01','2024-01-01'],
            ['nextYearExt','2024-02-29','UTC',1,true,0,'2025-01-01','2026-01-01'],
            ['yesterdayExt','2025-09-10 00:30','Asia/Tokyo',1,true,0,'2025-09-09','2025-09-10'],
            ['tomorrowExt','2025-09-10 23:30','Pacific/Auckland',1,true,0,'2025-09-11','2025-09-12'],
            ['yesterdayExt','2025-09-10 23:30','America/Denver',1,true,0,'2025-09-09','2025-09-10'],
            ['currentWeekExt','2025-09-09','UTC',1,true,1,'2025-09-08','2025-09-15'],
            ['currentWeekExt','2025-09-07','UTC',1,true,1,'2025-09-01','2025-09-08'],
            ['currentMonthPastExt','2025-09-09 23:30','America/Denver',1,true,0,'2025-09-01','2025-09-09'],
            ['currentMonthFutureExt','2025-09-09 23:30','America/Denver',1,true,0,'2025-09-09','2025-10-01'],
            ['lastXHoursExt','2025-03-09 03:30','America/Denver',1,false,0,'2025-03-09 08:00:00','2025-03-09 09:00:00'],
            ['nextXMinutesExt','2025-03-09 01:59:30','America/Denver',1,false,0,'2025-03-09 09:00:00','2025-03-09 09:01:00'],
            ['currentHourExt','2025-11-02T01:30:00-07:00','America/Denver',1,false,0,'2025-11-02 08:00:00','2025-11-02 09:00:00'],
            ['lastXHoursExt','2025-11-02T01:30:00-07:00','America/Denver',1,false,0,'2025-11-02 07:00:00','2025-11-02 08:00:00'],
            ['todayPastExt','2025-03-09 03:30','America/Denver',1,false,0,'2025-03-09 07:00:00','2025-03-09 09:30:00'],
            ['currentWeekExt','2025-03-09 03:30','America/Denver',1,false,0,'2025-03-09 07:00:00','2025-03-16 06:00:00'],
            ['lastSevenDaysExt','2025-09-09','UTC',1,true,0,'2025-09-02','2025-09-09'],
            ['lastXDaysExt','2025-09-09','UTC',2,true,0,'2025-09-07','2025-09-09'],
            ['nextXDaysExt','2025-09-09','UTC',2,true,0,'2025-09-10','2025-09-12'],
            ['lastWeekExt','2025-09-09','UTC',1,true,0,'2025-08-31','2025-09-07'],
            ['nextWeekExt','2025-09-09','UTC',1,true,0,'2025-09-14','2025-09-21'],
            ['upToNextXWeeksExt','2025-09-09','UTC',2,true,0,null,'2025-09-28'],
            ['upToNextXWeeksFutureExt','2025-09-09','UTC',2,true,0,'2025-09-09','2025-09-28'],
            ['afterNextXWeeksExt','2025-09-09','UTC',2,true,0,'2025-09-28',null],
            ['beforeXWeeksAgoExt','2025-09-09','UTC',2,true,0,null,'2025-08-24'],
            ['afterXWeeksAgoExt','2025-09-09','UTC',2,true,0,'2025-08-31',null],
            ['afterXWeeksAgoPastExt','2025-09-09','UTC',2,true,0,'2025-08-31','2025-09-09'],
        ];
    }

    public function testDateOnlyRejectsSubDayFilters(): void
    {
        $this->expectException(BadRequest::class);
        (new Core())->range('currentMinuteExt', new DateTime(), 1, 0, true);
    }
}
