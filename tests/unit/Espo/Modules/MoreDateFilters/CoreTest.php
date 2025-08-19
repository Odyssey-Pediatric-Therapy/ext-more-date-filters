<?php declare(strict_types=1);

namespace tests\unit\Espo\Modules\MoreDateFilters;

use DateTime;
use DateTimeZone;

use PHPUnit\Framework\TestCase;

use Espo\Modules\MoreDateFilters\Services\Core;

class CoreTest extends TestCase
{
  private Core $core;

  public function __construct() {
    parent::__construct();

    $this->core = new Core();
  }

  public function testGetWeekStartEnd_date(): void {
    $cases = array(
      ["2025-08-10", "UTC",            "2025-08-10", "2025-08-17", 0],
      ["2025-08-14", "UTC",            "2025-08-10", "2025-08-17", 0],
      ["2025-08-16", "UTC",            "2025-08-10", "2025-08-17", 0],
      ["2025-08-17", "UTC",            "2025-08-17", "2025-08-24", 0],
      ["2024-01-01", "UTC",            "2023-12-31", "2024-01-07", 0],
      ["2024-01-31", "UTC",            "2024-01-28", "2024-02-04", 0],
      ["2024-01-31", "America/Denver", "2024-01-28", "2024-02-04", 0],
      ["2024-12-30", "America/Denver", "2024-12-29", "2025-01-05", 0],
      ["2024-12-31", "America/Denver", "2024-12-29", "2025-01-05", 0],
      ["2024-02-29", "America/Denver", "2024-02-25", "2024-03-03", 0],
      ["2023-02-28", "America/Denver", "2023-02-26", "2023-03-05", 0],
      ["2025-08-14", "America/Denver", "2025-08-10", "2025-08-31", 2],
      ["2024-12-30", "America/Denver", "2024-12-29", "2025-02-02", 4],
    );

    foreach($cases as [$date, $timezone, $start, $end, $addlWeeks]) {
      $anchor = new DateTime($date, new DateTimeZone($timezone));

      list($from, $to) = $this->core->getWeekStartEnd(
        anchor: $anchor,
        addlWeeks: $addlWeeks
      );

      $this->assertEquals($start, $from->format("Y-m-d"));
      $this->assertEquals($end, $to->format("Y-m-d"));
      $this->assertEquals("UTC", $from->getTimezone()->getName());
      $this->assertEquals("UTC", $to->getTimezone()->getName());
    }
  }

  public function testGetWeekStartEnd_datetime(): void {
    $cases = array(
      ["2025-08-10", "14:30:00", "UTC",            "2025-08-10 00:00:00", "2025-08-17 00:00:00", 0],
      ["2025-08-14", "09:15:00", "UTC",            "2025-08-10 00:00:00", "2025-08-17 00:00:00", 0],
      ["2025-08-16", "23:59:59", "UTC",            "2025-08-10 00:00:00", "2025-08-17 00:00:00", 0],
      ["2025-08-17", "00:00:00", "UTC",            "2025-08-17 00:00:00", "2025-08-24 00:00:00", 0],
      ["2024-01-01", "08:00:00", "UTC",            "2023-12-31 00:00:00", "2024-01-07 00:00:00", 0],
      ["2024-01-31", "12:00:00", "UTC",            "2024-01-28 00:00:00", "2024-02-04 00:00:00", 0],
      ["2024-01-31", "12:00:00", "America/Denver", "2024-01-28 07:00:00", "2024-02-04 07:00:00", 0],
      ["2024-12-30", "15:45:00", "America/Denver", "2024-12-29 07:00:00", "2025-01-05 07:00:00", 0],
      ["2024-12-31", "10:30:00", "America/Denver", "2024-12-29 07:00:00", "2025-01-05 07:00:00", 0],
      ["2024-02-29", "18:20:00", "America/Denver", "2024-02-25 07:00:00", "2024-03-03 07:00:00", 0],
      ["2023-02-28", "07:00:00", "America/Denver", "2023-02-26 07:00:00", "2023-03-05 07:00:00", 0],
      ["2025-02-28", "13:15:00", "America/Denver", "2025-02-23 07:00:00", "2025-04-13 06:00:00", 6],
      ["2025-08-14", "13:15:00", "America/Denver", "2025-08-10 06:00:00", "2025-08-31 06:00:00", 2],
      ["2024-12-30", "22:30:00", "America/Denver", "2024-12-29 07:00:00", "2025-02-02 07:00:00", 4],
    );

    foreach($cases as [$date, $time, $timezone, $start, $end, $addlWeeks]) {
      $anchor = new DateTime("$date $time", new DateTimeZone($timezone));

      list($from, $to) = $this->core->getWeekStartEnd(
        anchor: $anchor,
        addlWeeks: $addlWeeks
      );

      $this->assertEquals($start, $from->format("Y-m-d H:i:s"));
      $this->assertEquals($end, $to->format("Y-m-d H:i:s"));
      $this->assertEquals("UTC", $from->getTimezone()->getName());
      $this->assertEquals("UTC", $to->getTimezone()->getName());
    }
  }

  public function testCoreGetFullDay_date(): void {
    $cases = array(
      ["2025-08-10", "UTC",            "2025-08-10", "2025-08-11", 0],
      ["2025-08-14", "UTC",            "2025-08-14", "2025-08-15", 0],
      ["2025-08-16", "UTC",            "2025-08-16", "2025-08-17", 0],
      ["2024-01-01", "UTC",            "2024-01-01", "2024-01-02", 0],
      ["2024-01-31", "UTC",            "2024-01-31", "2024-02-01", 0],
      ["2024-01-31", "America/Denver", "2024-01-31", "2024-02-01", 0],
      ["2024-12-30", "America/Denver", "2024-12-30", "2024-12-31", 0],
      ["2024-12-31", "America/Denver", "2024-12-31", "2025-01-01", 0],
      ["2024-02-29", "America/Denver", "2024-02-29", "2024-03-01", 0],
      ["2023-02-28", "America/Denver", "2023-02-28", "2023-03-01", 0],
      ["2025-08-14", "America/Denver", "2025-08-14", "2025-08-17", 2],
      ["2024-12-30", "America/Denver", "2024-12-30", "2025-01-04", 4],
    );

    foreach($cases as [$date, $timezone, $start, $end, $addlDays]) {
      $anchor = new DateTime($date, new DateTimeZone($timezone));

      list($from, $to) = $this->core->getFullDay(
        anchor: $anchor,
        addlDays: $addlDays,
      );

      $this->assertEquals($start, $from->format("Y-m-d"));
      $this->assertEquals($end, $to->format("Y-m-d"));
      $this->assertEquals("UTC", $from->getTimezone()->getName());
      $this->assertEquals("UTC", $to->getTimezone()->getName());
    }
  }

  public function testCoreGetFullDay_datetime(): void {
    $cases = array(
      ["2025-08-10", "14:30:00", "UTC",            "2025-08-10 00:00:00", "2025-08-11 00:00:00", 0],
      ["2025-08-14", "09:15:00", "UTC",            "2025-08-14 00:00:00", "2025-08-15 00:00:00", 0],
      ["2025-08-16", "23:59:59", "UTC",            "2025-08-16 00:00:00", "2025-08-17 00:00:00", 0],
      ["2024-01-01", "08:00:00", "UTC",            "2024-01-01 00:00:00", "2024-01-02 00:00:00", 0],
      ["2024-01-31", "12:00:00", "UTC",            "2024-01-31 00:00:00", "2024-02-01 00:00:00", 0],
      ["2024-01-31", "12:00:00", "America/Denver", "2024-01-31 07:00:00", "2024-02-01 07:00:00", 0],
      ["2024-12-30", "15:45:00", "America/Denver", "2024-12-30 07:00:00", "2024-12-31 07:00:00", 0],
      ["2024-12-31", "10:30:00", "America/Denver", "2024-12-31 07:00:00", "2025-01-01 07:00:00", 0],
      ["2024-02-29", "18:20:00", "America/Denver", "2024-02-29 07:00:00", "2024-03-01 07:00:00", 0],
      ["2023-02-28", "07:00:00", "America/Denver", "2023-02-28 07:00:00", "2023-03-01 07:00:00", 0],
      ["2025-03-08", "12:00:00", "America/Denver", "2025-03-08 07:00:00", "2025-03-10 06:00:00", 1],
      ["2025-08-14", "13:15:00", "America/Denver", "2025-08-14 06:00:00", "2025-08-17 06:00:00", 2],
      ["2024-12-30", "22:30:00", "America/Denver", "2024-12-30 07:00:00", "2025-01-04 07:00:00", 4],
    );

    foreach($cases as [$date, $time, $timezone, $start, $end, $addlDays]) {
      $anchor = new DateTime("$date $time", new DateTimeZone($timezone));

      list($from, $to) = $this->core->getFullDay(
        anchor: $anchor,
        addlDays: $addlDays
      );

      $this->assertEquals($start, $from->format("Y-m-d H:i:s"));
      $this->assertEquals($end, $to->format("Y-m-d H:i:s"));
      $this->assertEquals("UTC", $from->getTimezone()->getName());
      $this->assertEquals("UTC", $to->getTimezone()->getName());
    }
  }

  public function testCoreGetFullMonth_date(): void {
    $cases = array(
      ["2025-08-01", "UTC",            "2025-08-01", "2025-09-01", 0],
      ["2025-08-15", "UTC",            "2025-08-01", "2025-09-01", 0],
      ["2025-08-15", "UTC",            "2025-08-01", "2025-10-01", 1],
      ["2025-08-15", "UTC",            "2025-08-01", "2025-11-01", 2],
      ["2024-02-29", "UTC",            "2024-02-01", "2024-03-01", 0],
      ["2024-02-28", "UTC",            "2024-02-01", "2024-03-01", 0],
      ["2024-12-31", "UTC",            "2024-12-01", "2025-01-01", 0],
      ["2025-03-09", "UTC",            "2025-03-01", "2025-04-01", 0],
      ["2025-11-02", "UTC",            "2025-11-01", "2025-12-01", 0],
      ["2024-11-30", "UTC",            "2024-11-01", "2025-02-01", 2],
      ["2024-12-31", "America/Denver", "2024-12-01", "2025-01-01", 0],
      ["2025-03-09", "America/Denver", "2025-03-01", "2025-04-01", 0],
      ["2025-11-02", "America/Denver", "2025-11-01", "2025-12-01", 0],
      ["2024-11-30", "America/Denver", "2024-11-01", "2025-02-01", 2],
    );

    foreach($cases as [$date, $timezone, $start, $end, $addlMonths]) {
      $anchor = new DateTime($date, new DateTimeZone($timezone));
      list($from, $to) = $this->core->getFullMonth(
        anchor: $anchor,
        addlMonths: $addlMonths
      );

      $this->assertEquals($start, $from->format("Y-m-d"));
      $this->assertEquals($end, $to->format("Y-m-d"));
      $this->assertEquals("UTC", $from->getTimezone()->getName());
      $this->assertEquals("UTC", $to->getTimezone()->getName());
    }
  }

  public function testCoreGetFullMonth_datetime(): void {
    $cases = array(
      ["2025-08-01", "14:30:00", "UTC",            "2025-08-01 00:00:00", "2025-09-01 00:00:00", 0],
      ["2025-08-15", "09:15:00", "UTC",            "2025-08-01 00:00:00", "2025-09-01 00:00:00", 0],
      ["2025-08-15", "23:59:59", "UTC",            "2025-08-01 00:00:00", "2025-10-01 00:00:00", 1],
      ["2025-08-15", "00:00:00", "UTC",            "2025-08-01 00:00:00", "2025-11-01 00:00:00", 2],
      ["2024-02-29", "08:00:00", "UTC",            "2024-02-01 00:00:00", "2024-03-01 00:00:00", 0],
      ["2024-02-28", "12:00:00", "UTC",            "2024-02-01 00:00:00", "2024-03-01 00:00:00", 0],
      ["2024-12-31", "12:00:00", "UTC",            "2024-12-01 00:00:00", "2025-01-01 00:00:00", 0],
      ["2025-03-09", "10:30:00", "UTC",            "2025-03-01 00:00:00", "2025-04-01 00:00:00", 0],
      ["2025-11-02", "07:00:00", "UTC",            "2025-11-01 00:00:00", "2025-12-01 00:00:00", 0],
      ["2024-11-30", "13:15:00", "UTC",            "2024-11-01 00:00:00", "2025-02-01 00:00:00", 2],
      ["2024-12-31", "15:45:00", "America/Denver", "2024-12-01 07:00:00", "2025-01-01 07:00:00", 0],
      ["2025-03-09", "18:20:00", "America/Denver", "2025-03-01 07:00:00", "2025-04-01 06:00:00", 0],
      ["2025-11-02", "13:15:00", "America/Denver", "2025-11-01 06:00:00", "2025-12-01 07:00:00", 0],
      ["2024-11-30", "22:30:00", "America/Denver", "2024-11-01 06:00:00", "2025-02-01 07:00:00", 2],
    );

    foreach($cases as [$date, $time, $timezone, $start, $end, $addlMonths]) {
      $anchor = new DateTime("$date $time", new DateTimeZone($timezone));

      list($from, $to) = $this->core->getFullMonth(
        anchor: $anchor,
        addlMonths: $addlMonths
      );

      $this->assertEquals($start, $from->format("Y-m-d H:i:s"));
      $this->assertEquals($end, $to->format("Y-m-d H:i:s"));
      $this->assertEquals("UTC", $from->getTimezone()->getName());
      $this->assertEquals("UTC", $to->getTimezone()->getName());
    }
  }

  public function testCoreGetFullQuarter_date(): void {
    $cases = array(
      ["2025-08-01", "UTC",            "2025-07-01", "2025-10-01", 0],
      ["2025-08-15", "UTC",            "2025-07-01", "2025-10-01", 0],
      ["2025-08-15", "UTC",            "2025-07-01", "2026-01-01", 1],
      ["2024-02-29", "UTC",            "2024-01-01", "2024-04-01", 0],
      ["2024-02-28", "UTC",            "2024-01-01", "2024-04-01", 0],
      ["2024-12-31", "UTC",            "2024-10-01", "2025-01-01", 0],
      ["2025-03-09", "UTC",            "2025-01-01", "2025-04-01", 0],
      ["2025-11-02", "UTC",            "2025-10-01", "2026-01-01", 0],
      ["2024-11-30", "UTC",            "2024-10-01", "2025-04-01", 1],
      ["2024-11-30", "UTC",            "2024-10-01", "2025-07-01", 2],
      ["2025-08-14", "UTC",            "2025-07-01", "2025-10-01", 0],
      ["2024-12-31", "America/Denver", "2024-10-01", "2025-01-01", 0],
      ["2025-03-09", "America/Denver", "2025-01-01", "2025-04-01", 0],
      ["2025-11-02", "America/Denver", "2025-10-01", "2026-01-01", 0],
      ["2024-11-30", "America/Denver", "2024-10-01", "2025-04-01", 1],
      ["2024-11-30", "America/Denver", "2024-10-01", "2025-07-01", 2],
      ["2024-12-30", "America/Denver", "2024-10-01", "2025-01-01", 0],
      ["2024-12-30", "America/Denver", "2024-10-01", "2025-07-01", 2],
      ["2024-12-31", "America/Denver", "2024-10-01", "2026-01-01", 4],
    );

    foreach($cases as [$date, $timezone, $start, $end, $addlQuarters]) {
      $anchor = new DateTime($date, new DateTimeZone($timezone));
      list($from, $to) = $this->core->getFullQuarter(
        anchor: $anchor,
        addlQuarters: $addlQuarters
      );

      $this->assertEquals($start, $from->format("Y-m-d"));
      $this->assertEquals($end, $to->format("Y-m-d"));
      $this->assertEquals("UTC", $from->getTimezone()->getName());
      $this->assertEquals("UTC", $to->getTimezone()->getName());
    }
  }

  public function testCoreGetFullQuarter_datetime(): void {
    $cases = array(
      ["2025-08-01", "14:30:00", "UTC",            "2025-07-01 00:00:00", "2025-10-01 00:00:00", 0],
      ["2025-08-15", "09:15:00", "UTC",            "2025-07-01 00:00:00", "2025-10-01 00:00:00", 0],
      ["2025-08-15", "23:59:59", "UTC",            "2025-07-01 00:00:00", "2026-01-01 00:00:00", 1],
      ["2024-02-29", "08:00:00", "UTC",            "2024-01-01 00:00:00", "2024-04-01 00:00:00", 0],
      ["2024-02-28", "12:00:00", "UTC",            "2024-01-01 00:00:00", "2024-04-01 00:00:00", 0],
      ["2024-12-31", "12:00:00", "UTC",            "2024-10-01 00:00:00", "2025-01-01 00:00:00", 0],
      ["2025-03-09", "10:30:00", "UTC",            "2025-01-01 00:00:00", "2025-04-01 00:00:00", 0],
      ["2025-11-02", "07:00:00", "UTC",            "2025-10-01 00:00:00", "2026-01-01 00:00:00", 0],
      ["2024-11-30", "13:15:00", "UTC",            "2024-10-01 00:00:00", "2025-04-01 00:00:00", 1],
      ["2025-08-15", "12:00:00", "UTC",            "2025-07-01 00:00:00", "2026-07-01 00:00:00", 3],
      ["2024-11-30", "12:00:00", "UTC",            "2024-10-01 00:00:00", "2026-01-01 00:00:00", 4],
      ["2024-12-31", "15:45:00", "America/Denver", "2024-10-01 06:00:00", "2025-01-01 07:00:00", 0],
      ["2025-02-15", "12:00:00", "America/Denver", "2025-01-01 07:00:00", "2025-04-01 06:00:00", 0],
      ["2025-03-09", "18:20:00", "America/Denver", "2025-01-01 07:00:00", "2025-04-01 06:00:00", 0],
      ["2025-11-02", "13:15:00", "America/Denver", "2025-10-01 06:00:00", "2026-01-01 07:00:00", 0],
      ["2024-11-30", "22:30:00", "America/Denver", "2024-10-01 06:00:00", "2025-04-01 06:00:00", 1],
      ["2025-08-15", "12:00:00", "America/Denver", "2025-07-01 06:00:00", "2026-07-01 06:00:00", 3],
      ["2024-11-30", "12:00:00", "America/Denver", "2024-10-01 06:00:00", "2026-01-01 07:00:00", 4],
    );

    foreach($cases as [$date, $time, $timezone, $start, $end, $addlQuarters]) {
      $anchor = new DateTime("$date $time", new DateTimeZone($timezone));

      list($from, $to) = $this->core->getFullQuarter(
        anchor: $anchor,
        addlQuarters: $addlQuarters
      );

      $this->assertEquals($start, $from->format("Y-m-d H:i:s"));
      $this->assertEquals($end, $to->format("Y-m-d H:i:s"));
      $this->assertEquals("UTC", $from->getTimezone()->getName());
      $this->assertEquals("UTC", $to->getTimezone()->getName());
    }
  }

  public function testCoreGetFullYear_date(): void {
    $cases = array(
      ["2025-08-01", "UTC",            "2025-01-01", "2026-01-01", 0],
      ["2025-08-15", "UTC",            "2025-01-01", "2026-01-01", 0],
      ["2024-02-29", "UTC",            "2024-01-01", "2025-01-01", 0],
      ["2024-02-28", "UTC",            "2024-01-01", "2025-01-01", 0],
      ["2024-12-31", "UTC",            "2024-01-01", "2025-01-01", 0],
      ["2025-03-09", "UTC",            "2025-01-01", "2026-01-01", 0],
      ["2025-11-02", "UTC",            "2025-01-01", "2026-01-01", 0],
      ["2024-11-30", "UTC",            "2024-01-01", "2025-01-01", 0],
      ["2024-11-30", "UTC",            "2024-01-01", "2026-01-01", 1],
      ["2024-12-31", "UTC",            "2024-01-01", "2027-01-01", 2],
      ["2024-12-31", "UTC",            "2024-01-01", "2028-01-01", 3],
      ["2024-12-31", "America/Denver", "2024-01-01", "2025-01-01", 0],
      ["2025-03-09", "America/Denver", "2025-01-01", "2026-01-01", 0],
      ["2025-11-02", "America/Denver", "2025-01-01", "2026-01-01", 0],
      ["2024-11-30", "America/Denver", "2024-01-01", "2025-01-01", 0],
      ["2024-11-30", "America/Denver", "2024-01-01", "2026-01-01", 1],
      ["2024-12-31", "America/Denver", "2024-01-01", "2027-01-01", 2],
      ["2024-12-31", "America/Denver", "2024-01-01", "2028-01-01", 3],
    );

    foreach($cases as [$date, $timezone, $start, $end, $addlYears]) {
      $anchor = new DateTime($date, new DateTimeZone($timezone));
      list($from, $to) = $this->core->getFullYear(
        anchor: $anchor,
        addlYears: $addlYears
      );

      $this->assertEquals($start, $from->format("Y-m-d"));
      $this->assertEquals($end, $to->format("Y-m-d"));
      $this->assertEquals("UTC", $from->getTimezone()->getName());
      $this->assertEquals("UTC", $to->getTimezone()->getName());
    }
  }

  public function testCoreGetFullYear_datetime(): void {
    $cases = array(
      ["2025-01-01", "00:00:00", "UTC",            "2025-01-01 00:00:00", "2026-01-01 00:00:00", 0],
      ["2025-08-01", "14:30:00", "UTC",            "2025-01-01 00:00:00", "2026-01-01 00:00:00", 0],
      ["2025-08-15", "09:15:00", "UTC",            "2025-01-01 00:00:00", "2026-01-01 00:00:00", 0],
      ["2024-02-28", "00:00:00", "UTC",            "2024-01-01 00:00:00", "2025-01-01 00:00:00", 0],
      ["2024-02-29", "23:59:59", "UTC",            "2024-01-01 00:00:00", "2025-01-01 00:00:00", 0],
      ["2024-12-31", "08:00:00", "UTC",            "2024-01-01 00:00:00", "2025-01-01 00:00:00", 0],
      ["2024-12-31", "23:59:59", "UTC",            "2024-01-01 00:00:00", "2025-01-01 00:00:00", 0],
      ["2024-11-30", "23:59:59", "UTC",            "2024-01-01 00:00:00", "2026-01-01 00:00:00", 1],
      ["2025-03-09", "12:00:00", "UTC",            "2025-01-01 00:00:00", "2028-01-01 00:00:00", 2],
      ["2024-11-30", "16:45:00", "UTC",            "2024-01-01 00:00:00", "2027-01-01 00:00:00", 2],
      ["2024-12-31", "16:45:00", "UTC",            "2024-01-01 00:00:00", "2028-01-01 00:00:00", 3],
      ["2024-01-01", "00:00:00", "America/Denver", "2024-01-01 07:00:00", "2025-01-01 07:00:00", 0],
      ["2024-12-31", "12:00:00", "America/Denver", "2024-01-01 07:00:00", "2025-01-01 07:00:00", 0],
      ["2025-03-09", "15:45:00", "America/Denver", "2025-01-01 07:00:00", "2026-01-01 07:00:00", 0],
      ["2025-11-02", "06:00:00", "America/Denver", "2025-01-01 07:00:00", "2026-01-01 07:00:00", 0],
      ["2025-11-02", "23:59:59", "America/Denver", "2025-01-01 07:00:00", "2026-01-01 07:00:00", 0],
      ["2024-11-30", "06:00:00", "America/Denver", "2024-01-01 07:00:00", "2026-01-01 07:00:00", 1],
      ["2025-03-09", "12:00:00", "America/Denver", "2025-01-01 07:00:00", "2028-01-01 07:00:00", 2],
      ["2024-11-30", "22:30:00", "America/Denver", "2024-01-01 07:00:00", "2027-01-01 07:00:00", 2],
      ["2024-12-31", "12:00:00", "America/Denver", "2024-01-01 07:00:00", "2028-01-01 07:00:00", 3],
    );

    foreach($cases as [$date, $time, $timezone, $start, $end, $addlYears]) {
      $anchor = new DateTime("$date $time", new DateTimeZone($timezone));

      list($from, $to) = $this->core->getFullYear(
        anchor: $anchor,
        addlYears: $addlYears
      );

      $this->assertEquals($start, $from->format("Y-m-d H:i:s"));
      $this->assertEquals($end, $to->format("Y-m-d H:i:s"));
      $this->assertEquals("UTC", $from->getTimezone()->getName());
      $this->assertEquals("UTC", $to->getTimezone()->getName());
    }
  }
}