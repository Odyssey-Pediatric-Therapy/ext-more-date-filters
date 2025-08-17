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
use Espo\Modules\MoreDateFilters\Services\Core;

class TestQuarters extends Base implements TestInterface
{
  public function __construct(
    protected Config $config,
    protected Core $core,
    protected ItemConverterFactory $itemConverterFactory,
    protected Log $log,
    protected QueryBuilder $queryBuilder,
    protected User $user,
  ) {
    $this->name = explode("\\", self::class)[1];
    $this->local = new DateTimeZone($this->config->get("timeZone", "America/Denver"));
  }

  protected function getTestList(): array {
    return [
      // "testCurrentQuarter",
      // "testLastQuarter",
      // "testNextQuarter",
      // "testLastXQuarters",
      // "testNextXQuarters",
    ];
  }

  protected function testNextQuarter(): void {
    $today = new DateTime("today", $this->local);

    $nextMonthStart = (clone $today)->modify("first day of next month");

    list($startLcl, $endLcl) = $this->core->getFullMonth(anchor: $nextMonthStart);
    list($startUtc, $endUtc) = $this->core->getFullMonth(anchor: $nextMonthStart, returnUtc: true);

    $configurations = array(
      ["type" => "nextMonthDate",     "expected" => ["AND" => ["dateEnd>=" => $startLcl->format("Y-m-d"),       "dateEnd<"  => $endLcl->format("Y-m-d")]]],
      ["type" => "nextMonthDatetime", "expected" => ["AND" => ["dateEnd>=" => $startUtc->format("Y-m-d H:i:s"), "dateEnd<"  => $endUtc->format("Y-m-d H:i:s")]]],
    );

    $this->runConfigurations($configurations);
  }

  protected function testLastXQuarters(): void {
    $today = new DateTime("today", $this->local);

    for($i = 0; $i < 5; $i++) {
      $lastMonthStart = (clone $today)->modify("-$i months")->modify("first day of this month");

      list($startLcl, $endLcl) = $this->core->getFullMonth(anchor: $lastMonthStart, addlMonths: $i - 1);
      list($startUtc, $endUtc) = $this->core->getFullMonth(anchor: $lastMonthStart, addlMonths: $i - 1, returnUtc: true);

      $configurations = array(
        ["type" => "lastXMonthsDate",     "expected" => ["AND" => ["dateEnd>=" => $startLcl->format("Y-m-d"),       "dateEnd<"  => $endLcl->format("Y-m-d")]],  "value" => $i],
        ["type" => "lastXMonthsDatetime",    "expected" => ["AND" => ["dateEnd>=" => $startUtc->format("Y-m-d H:i:s"), "dateEnd<"  => $endUtc->format("Y-m-d H:i:s")]],  "value" => $i],
      );

      $this->runConfigurations($configurations);
    }
  }

  protected function testNextXQuarters(): void {
    $today = new DateTime("today", $this->local);

    $nextMonthStart = (clone $today)->modify("first day of next month");

    for($i = 0; $i < 5; $i++) {
      $startLcl = $this->core->getFullMonth(anchor: $nextMonthStart, addlMonths: 0)[0];
      $endLcl = $this->core->getFullMonth(anchor: $nextMonthStart, addlMonths: $i - 1)[1];

      $startUtc = $this->core->getFullMonth(anchor: $nextMonthStart, addlMonths: 0, returnUtc: true)[0];
      $endUtc = $this->core->getFullMonth(anchor: $nextMonthStart, addlMonths: $i - 1, returnUtc: true)[1];

      $configurations = array(
        ["type" => "nextXMonthsDate",     "expected" => ["AND" => ["dateEnd>=" => $startLcl->format("Y-m-d"),       "dateEnd<"  => $endLcl->format("Y-m-d")]],  "value" => $i],
        ["type" => "nextXMonthsDatetime",    "expected" => ["AND" => ["dateEnd>=" => $startUtc->format("Y-m-d H:i:s"), "dateEnd<"  => $endUtc->format("Y-m-d H:i:s")]],  "value" => $i],
      );

      $this->runConfigurations($configurations);
    }
  }
}
