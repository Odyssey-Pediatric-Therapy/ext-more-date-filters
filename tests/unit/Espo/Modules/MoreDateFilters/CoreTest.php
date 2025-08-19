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

      $this->assertEquals($from->format("Y-m-d"), $start);
      $this->assertEquals($to->format("Y-m-d"), $end);
      $this->assertEquals($from->getTimezone()->getName(), "UTC");
      $this->assertEquals($to->getTimezone()->getName(), "UTC");
    }
  }
}