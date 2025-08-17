<?php

namespace MoreDateFiltersTest;

use DateTime;
use DateTimeZone;

use Espo\Core\Exceptions\Error;
use Espo\Core\Select\Where\Item;
use Espo\Core\Select\Where\ItemConverterFactory;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;
use Espo\Entities\User;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;

use Espo\Modules\MoreDateFilters\Classes\Test\Base;
use Espo\Modules\MoreDateFilters\Classes\Test\Test as TestInterface;

use Espo\Modules\MoreDateFilters\Services\Core as Service;

class TestCore extends Base implements TestInterface
{
  public function __construct(
    protected Config $config,
    protected ItemConverterFactory $itemConverterFactory,
    protected Log $log,
    protected Service $service,
    protected QueryBuilder $queryBuilder,
    protected User $user,
  ) {
    $this->name = explode("\\", self::class)[1];
    $this->local = new DateTimeZone($this->config->get("timeZone", "America/Denver"));
  }

  protected function getTestList(): array {
    return [
      #"testCoreGetWeekStartEnd_date",
      "testCoreGetWeekStartEnd_datetime",
      # "testCoreGetFullDay_date",
      # "testCoreGetFullDay_datetime",
      # "testCoreGetFullMonth_date",
      # "testCoreGetFullMonth_datetime",
      # "testCoreGetFullQuarter_date",
      # "testCoreGetFullQuarter_datetime",
    ];
  }

  protected function testCoreGetWeekStartEnd_date(): void{
    $cases = array(
      [new DateTime("2025-08-10"), "2025-08-10", "2025-08-17", 0, false],
      [new DateTime("2025-08-14"), "2025-08-10", "2025-08-17", 0, false],
      [new DateTime("2025-08-16"), "2025-08-10", "2025-08-17", 0, false],
      [new DateTime("2025-08-17"), "2025-08-17", "2025-08-24", 0, false],
      [new DateTime("2024-01-01"), "2023-12-31", "2024-01-07", 0, false],
      [new DateTime("2024-01-31"), "2024-01-28", "2024-02-04", 0, false],
      [new DateTime("2024-01-31", $this->local), "2024-01-28", "2024-02-04", 0, true],
      [new DateTime("2024-12-30"), "2024-12-29", "2025-01-05", 0, false],
      [new DateTime("2024-12-31"), "2024-12-29", "2025-01-05", 0, false],
      [new DateTime("2024-02-29"), "2024-02-25", "2024-03-03", 0, false],
      [new DateTime("2023-02-28"), "2023-02-26", "2023-03-05", 0, false],
      [new DateTime("2025-08-14"), "2025-08-10", "2025-08-31", 2, false],
      [new DateTime("2024-12-30"), "2024-12-29", "2025-02-02", 4, false],
    );

    foreach($cases as [$anchor, $start, $end, $addlWeeks, $returnUtc]) {
      list($from, $to) = $this->service->getWeekStartEnd(anchor: $anchor, addlWeeks: $addlWeeks, returnUtc: $returnUtc);

      $this->assertEquals($start, $from->format("Y-m-d"));
      $this->assertEquals($end, $to->format("Y-m-d"));
    }
  }

  protected function testCoreGetWeekStartEnd_datetime(): void {
    $cases = array(
      /*
      [new DateTime("2025-08-09 23:59:59")              , "2025-08-03 23:59:59", "2025-08-10 23:59:59", 0, false],
      [new DateTime("2025-08-10 00:00:00")              , "2025-08-10 00:00:00", "2025-08-17 00:00:00", 0, false],
      [new DateTime("2025-08-14 12:30:45")              , "2025-08-10 12:30:45", "2025-08-17 12:30:45", 0, false],
      [new DateTime("2025-08-16 23:59:59")              , "2025-08-10 23:59:59", "2025-08-17 23:59:59", 0, false],
      [new DateTime("2025-08-17 23:59:59")              , "2025-08-17 23:59:59", "2025-08-24 23:59:59", 0, false],
      [new DateTime("2025-08-10 00:01:00")              , "2025-08-10 00:01:00", "2025-08-17 00:01:00", 0, false],
      [new DateTime("2025-08-10 00:01:00")              , "2025-08-10 00:01:00", "2025-10-05 00:01:00", 7, false],
      [new DateTime("2024-01-01 08:15:00")              , "2023-12-31 08:15:00", "2024-01-07 08:15:00", 0, false],
      [new DateTime("2024-01-31 23:59:59")              , "2024-01-28 23:59:59", "2024-02-04 23:59:59", 0, false],
      [new DateTime("2024-12-30 06:00:00")              , "2024-12-29 06:00:00", "2025-01-05 06:00:00", 0, false],
      [new DateTime("2024-12-31 15:15:15")              , "2024-12-29 15:15:15", "2025-01-05 15:15:15", 0, false],
      [new DateTime("2024-02-29 00:00:00")              , "2024-02-25 00:00:00", "2024-03-03 00:00:00", 0, false],
      [new DateTime("2024-02-29 00:00:00", $this->local), "2024-02-25 07:00:00", "2024-03-03 07:00:00", 0, true],
      [new DateTime("2024-02-29 00:00:00")              , "2024-02-25 00:00:00", "2024-03-03 00:00:00", 0, true],
      [new DateTime("2023-02-28 12:45:30")              , "2023-02-26 12:45:30", "2023-03-05 12:45:30", 0, false],
      */
      [new DateTime("2023-03-09 23:45:30")              , "2023-03-03 00:00:00", "2023-03-10 00:00:00", 0, false],
      [new DateTime("2023-03-09 23:45:30", $this->local), "2023-03-03 07:00:00", "2023-03-10 06:00:00", 0, false],
      [new DateTime("2023-03-09 23:45:30")              , "2023-03-03 00:00:00", "2023-03-10 00:00:00", 0, true],
      [new DateTime("2023-03-09 23:45:30", $this->local), "2023-03-03 07:00:00", "2023-03-10 06:00:00", 0, true],
    );

    foreach($cases as [$anchor, $start, $end, $addlWeeks, $returnUtc]) {
      list($from, $to) = $this->service->getWeekStartEnd(anchor: $anchor, addlWeeks: $addlWeeks, returnUtc: true);

      $this->assertEquals($start, $from->format("Y-m-d H:i:s"));
      $this->assertEquals($end, $to->format("Y-m-d H:i:s"));
    }
  }

  protected function testCoreGetFullDay_date(): void {
    $cases = array(
      [new DateTime("2025-08-14"), "2025-08-14", "2025-08-15", 0, false],
      [new DateTime("2025-08-01"), "2025-08-01", "2025-08-02", 0, false],
      [new DateTime("2025-08-14"), "2025-08-14", "2025-08-17", 2, false],
      [new DateTime("2024-12-31"), "2024-12-31", "2025-01-03", 2, false],
      [new DateTime("2025-08-14"), "2025-08-14", "2025-08-13", -2, false],
      [new DateTime("2024-02-28"), "2024-02-28", "2024-02-29", 0, false],
      [new DateTime("2024-02-29"), "2024-02-29", "2024-03-01", 0, false],
      [new DateTime("2024-01-28", $this->local), "2024-01-28", "2024-01-29", 0, true],
      [new DateTime("2024-11-02"), "2024-11-02", "2024-11-03", 0, false],
      [new DateTime("2024-12-31"), "2024-12-31", "2025-01-01", 0, false],
      [new DateTime("2024-12-31", $this->local), "2024-12-31", "2025-01-02", 1, true],
    );

    foreach($cases as [$anchor, $start, $end, $addlDays, $returnUtc]) {
      list($from, $to) = $this->service->getFullDay(anchor: $anchor, addlDays: $addlDays, returnUtc: $returnUtc);

      $this->assertEquals($start, $from->format("Y-m-d"));
      $this->assertEquals($end, $to->format("Y-m-d"));
    }
  }

  protected function testCoreGetFullDay_datetime(): void {
    $cases = array(
      [new DateTime("2025-08-14 00:00:00"), "2025-08-14 00:00:00", "2025-08-15 00:00:00", 0, false],
      [new DateTime("2025-08-14 12:34:56"), "2025-08-14 00:00:00", "2025-08-15 00:00:00", 0, false],
      [new DateTime("2025-08-14 15:15:15"), "2025-08-14 00:00:00", "2025-08-17 00:00:00", 2, false],
      [new DateTime("2024-02-29 10:00:00"), "2024-02-29 00:00:00", "2024-03-01 00:00:00", 0, false],
      [new DateTime("2024-02-29 23:59:59"), "2024-02-29 00:00:00", "2024-03-01 00:00:00", 0, false],
      [new DateTime("2024-03-01 00:00:00"), "2024-03-01 00:00:00", "2024-03-02 00:00:00", 0, false],
      [new DateTime("2024-12-31 23:59:59"), "2024-12-31 00:00:00", "2025-01-03 00:00:00", 2, false],
      [new DateTime("2024-12-31 23:59:59", $this->local), "2024-12-31 07:00:00", "2025-01-02 07:00:00", 1, true],
      [new DateTime("2024-12-31 23:00:00", $this->local), "2024-12-31 07:00:00", "2025-01-01 07:00:00", 0, true],
      [new DateTime("2025-03-09 12:00:00", $this->local), "2025-03-09 07:00:00", "2025-03-10 07:00:00", 0, true],
      [new DateTime("2025-11-02 12:00:00", $this->local), "2025-11-02 06:00:00", "2025-11-03 06:00:00", 0, true],
    );

    foreach($cases as [$anchor, $start, $end, $addlDays, $returnUtc]) {
      list($from, $to) = $this->service->getFullDay(anchor: $anchor, addlDays: $addlDays, returnUtc: true);

      $this->assertEquals($start, $from->format("Y-m-d H:i:s"));
      $this->assertEquals($end, $to->format("Y-m-d H:i:s"));
    }
  }

  protected function testCoreGetFullMonth_date(): void {
    $cases = array(
      [new DateTime("2025-08-01"), "2025-08-01", "2025-09-01", 0, false],
      [new DateTime("2025-08-15"), "2025-08-01", "2025-09-01", 0, false],
      [new DateTime("2025-08-15"), "2025-08-01", "2025-10-01", 1, false],
      [new DateTime("2025-08-15"), "2025-08-01", "2025-11-01", 2, false],
      [new DateTime("2024-02-29"), "2024-02-01", "2024-03-01", 0, false],
      [new DateTime("2024-02-28"), "2024-02-01", "2024-03-01", 0, false],
      [new DateTime("2024-12-31"), "2024-12-01", "2025-01-01", 0, false],
      [new DateTime("2024-12-31", $this->local), "2024-12-01", "2025-01-01", 0, true],
      [new DateTime("2025-03-09"), "2025-03-01", "2025-04-01", 0, false],
      [new DateTime("2025-03-09", $this->local), "2025-03-01", "2025-04-01", 0, true],
      [new DateTime("2025-11-02"), "2025-11-01", "2025-12-01", 0, false],
      [new DateTime("2025-11-02", $this->local), "2025-11-01", "2025-12-01", 0, true],
      [new DateTime("2024-11-30"), "2024-11-01", "2025-02-01", 2, false],
      [new DateTime("2024-11-30", $this->local), "2024-11-01", "2025-02-01", 2, true],
    );

    foreach($cases as [$anchor, $start, $end, $addlMonths, $returnUtc]) {
      list($from, $to) = $this->service->getFullMonth(anchor: $anchor, addlMonths: $addlMonths, returnUtc: $returnUtc);

      $this->assertEquals($start, $from->format("Y-m-d"));
      $this->assertEquals($end, $to->format("Y-m-d"));
    }
  }

  protected function testCoreGetFullMonth_datetime(): void {
    $cases = array(
      // [new DateTime("2025-08-01 00:00:00"), "2025-08-01 00:00:00", "2025-09-01 00:00:00", 0, false],
      // [new DateTime("2025-08-15 12:34:56"), "2025-08-01 00:00:00", "2025-09-01 00:00:00", 0, false],
      // [new DateTime("2024-02-29 23:59:59"), "2024-02-01 00:00:00", "2024-03-01 00:00:00", 0, false],
      // [new DateTime("2024-02-28 12:30:45"), "2024-02-01 00:00:00", "2024-03-01 00:00:00", 0, false],
      // [new DateTime("2024-12-31 23:59:59"), "2024-12-01 00:00:00", "2025-01-01 00:00:00", 0, false],
      // [new DateTime("2025-08-01 00:00:00", $this->local), "2025-08-01 06:00:00", "2025-09-01 06:00:00", 0, true],
      // [new DateTime("2024-12-31 23:59:59", $this->local), "2024-12-01 07:00:00", "2025-01-01 07:00:00", 0, true],
      [new DateTime("2025-03-09 12:00:00", $this->local), "2025-03-01 07:00:00", "2025-04-01 06:00:00", 0, true],
      // [new DateTime("2025-11-02 12:00:00", $this->local), "2025-11-01 06:00:00", "2025-12-01 07:00:00", 0, true],
      // [new DateTime("2024-11-30 08:00:00"), "2024-11-01 00:00:00", "2025-02-01 00:00:00", 2, false],
      // [new DateTime("2024-11-30 08:00:00", $this->local), "2024-11-01 06:00:00", "2025-02-01 07:00:00", 2, true],
    );

    foreach($cases as [$anchor, $start, $end, $addlMonths, $returnUtc]) {
      list($from, $to) = $this->service->getFullMonth(anchor: $anchor, addlMonths: $addlMonths, returnUtc: true);

      $this->assertEquals($start, $from->format("Y-m-d H:i:s"));
      $this->assertEquals($end, $to->format("Y-m-d H:i:s"));
    }
  }

  protected function testCoreGetFullQuarter_date(): void {
    $cases = array(
      [new DateTime("2025-08-01"), "2025-07-01", "2025-10-01", 0, false],
      [new DateTime("2025-08-15"), "2025-07-01", "2025-10-01", 0, false],
      [new DateTime("2025-08-15"), "2025-07-01", "2026-01-01", 1, false],
      [new DateTime("2024-02-29"), "2024-01-01", "2024-07-01", 2, false],
      [new DateTime("2024-02-28"), "2024-01-01", "2024-10-01", 3, false],
      [new DateTime("2024-12-31"), "2024-10-01", "2025-10-01", 4, false],
      [new DateTime("2024-02-29"), "2024-01-01", "2024-04-01", 0, false],
      [new DateTime("2024-02-28"), "2024-01-01", "2024-04-01", 0, false],
      [new DateTime("2024-12-31"), "2024-10-01", "2025-01-01", 0, false],
      [new DateTime("2024-12-31", $this->local), "2024-10-01", "2025-01-01", 0, true],
      [new DateTime("2025-03-09"), "2025-01-01", "2025-04-01", 0, false],
      [new DateTime("2025-03-09", $this->local), "2025-01-01", "2025-04-01", 0, true],
      [new DateTime("2025-11-02"), "2025-10-01", "2026-01-01", 0, false],
      [new DateTime("2025-11-02", $this->local), "2025-10-01", "2026-01-01", 0, true],
    );

    foreach($cases as [$anchor, $start, $end, $addlQuarters, $returnUtc]) {
      list($from, $to) = $this->service->getFullQuarter(anchor: $anchor, addlQuarters: $addlQuarters, returnUtc: $returnUtc);

      $this->assertEquals($start, $from->format("Y-m-d"));
      $this->assertEquals($end, $to->format("Y-m-d"));
    }
  }

  protected function testCoreGetFullQuarter_datetime(): void {
    $cases = array(
      [new DateTime("2025-08-01 00:00:00"), "2025-07-01 00:00:00", "2025-10-01 00:00:00", 0, false],
      [new DateTime("2025-08-15 12:34:56"), "2025-07-01 00:00:00", "2025-10-01 00:00:00", 0, false],
      [new DateTime("2024-02-29 23:59:59"), "2024-01-01 00:00:00", "2024-04-01 00:00:00", 0, false],
      [new DateTime("2024-02-28 12:30:45"), "2024-01-01 00:00:00", "2024-04-01 00:00:00", 0, false],
      [new DateTime("2024-12-31 23:59:59"), "2024-10-01 00:00:00", "2025-01-01 00:00:00", 0, false],
      [new DateTime("2025-08-15 12:34:56"), "2025-07-01 00:00:00", "2026-01-01 00:00:00", 1, false],
      [new DateTime("2024-02-29 23:59:59"), "2024-01-01 00:00:00", "2024-07-01 00:00:00", 2, false],
      [new DateTime("2024-02-28 12:00:00"), "2024-01-01 00:00:00", "2024-10-01 00:00:00", 3, false],
      [new DateTime("2024-12-31 08:15:30"), "2024-10-01 00:00:00", "2025-10-01 00:00:00", 4, false],
      [new DateTime("2025-08-15 12:34:56", $this->local), "2025-07-01 06:00:00", "2026-01-01 06:00:00", 1, true],
      [new DateTime("2024-02-29 23:59:59", $this->local), "2024-01-01 07:00:00", "2024-07-01 07:00:00", 2, true],
      [new DateTime("2024-02-28 12:00:00", $this->local), "2024-01-01 07:00:00", "2024-10-01 07:00:00", 3, true],
      [new DateTime("2024-12-31 08:15:30", $this->local), "2024-10-01 07:00:00", "2025-10-01 07:00:00", 4, true],
      [new DateTime("2025-08-01 00:00:00", $this->local), "2025-07-01 06:00:00", "2025-10-01 06:00:00", 0, true],
      [new DateTime("2024-12-31 23:59:59", $this->local), "2024-10-01 07:00:00", "2025-01-01 07:00:00", 0, true],
      [new DateTime("2025-03-09 12:00:00", $this->local), "2025-01-01 07:00:00", "2025-04-01 06:00:00", 0, true],
      [new DateTime("2025-11-02 12:30:45", $this->local), "2025-10-01 06:00:00", "2026-01-01 07:00:00", 0, true],
    );

    foreach($cases as [$anchor, $start, $end, $addlQuarters, $returnUtc]) {
      list($from, $to) = $this->service->getFullQuarter(anchor: $anchor, addlQuarters: $addlQuarters, returnUtc: true);

      $this->assertEquals($start, $from->format("Y-m-d H:i:s"));
      $this->assertEquals($end, $to->format("Y-m-d H:i:s"));
    }
  }

  // protected function testCoreGetFullYear_date(): void {

  // }

  // protected function testCoreGetFullYear_datetime(): void {

  // }
}
