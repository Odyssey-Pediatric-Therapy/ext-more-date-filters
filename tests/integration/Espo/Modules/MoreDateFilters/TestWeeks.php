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

class TestWeeks extends Base implements TestInterface
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
      "testCurrentWeek",
      "testLastWeek",
      "testNextWeek",
      "testLastXWeeks",
      "testNextXWeeks",
    ];
  }

  protected function testCurrentWeek(): void {
    $today = new DateTime("today", $this->local);
    list($startLcl, $endLcl) = $this->core->getWeekStartEnd(anchor: $today);
    list($startUtc, $endUtc) = $this->core->getWeekStartEnd(anchor: $today, returnUtc: true);

    $configurations = array(
      ["type" => "currentWeekDate",     "expected" => ["AND" => ["dateEnd>=" => $startLcl->format("Y-m-d"),       "dateEnd<"  => $endLcl->format("Y-m-d")]]],
      ["type" => "currentWeekDatetime", "expected" => ["AND" => ["dateEnd>=" => $startUtc->format("Y-m-d H:i:s"), "dateEnd<"  => $endUtc->format("Y-m-d H:i:s")]]],
    );

    $this->runConfigurations($configurations);
  }

  protected function testLastWeek(): void {
    $today = new DateTime("today", $this->local);

    $lastWeekStart = (clone $today)->modify("-7 days");

    list($startLcl, $endLcl) = $this->core->getWeekStartEnd(anchor: $lastWeekStart);
    list($startUtc, $endUtc) = $this->core->getWeekStartEnd(anchor: $lastWeekStart, returnUtc: true);

    $configurations = array(
      ["type" => "lastWeekDate",     "expected" => ["AND" => ["dateEnd>=" => $startLcl->format("Y-m-d"),       "dateEnd<"  => $endLcl->format("Y-m-d")]]],
      ["type" => "lastWeekDatetime", "expected" => ["AND" => ["dateEnd>=" => $startUtc->format("Y-m-d H:i:s"), "dateEnd<"  => $endUtc->format("Y-m-d H:i:s")]]],
    );

    $this->runConfigurations($configurations);
  }

  protected function testNextWeek(): void {
    $today = new DateTime("today", $this->local);

    $nextWeekStart = (clone $today)->modify("+7 days");

    list($startLcl, $endLcl) = $this->core->getWeekStartEnd(anchor: $nextWeekStart);
    list($startUtc, $endUtc) = $this->core->getWeekStartEnd(anchor: $nextWeekStart, returnUtc: true);

    $configurations = array(
      ["type" => "nextWeekDate",     "expected" => ["AND" => ["dateEnd>=" => $startLcl->format("Y-m-d"),       "dateEnd<"  => $endLcl->format("Y-m-d")]]],
      ["type" => "nextWeekDatetime", "expected" => ["AND" => ["dateEnd>=" => $startUtc->format("Y-m-d H:i:s"), "dateEnd<"  => $endUtc->format("Y-m-d H:i:s")]]],
    );

    $this->runConfigurations($configurations);
  }

  protected function testLastXWeeks(): void {
    $today = new DateTime("today", $this->local);

    for($i = 0; $i < 5; $i++) {
      $days = $i * 7;
      $lastWeekStart = (clone $today)->modify("-$days days");

      list($startLcl, $endLcl) = $this->core->getWeekStartEnd(anchor: $lastWeekStart, addlWeeks: $i - 1);
      list($startUtc, $endUtc) = $this->core->getWeekStartEnd(anchor: $lastWeekStart, addlWeeks: $i - 1, returnUtc: true);

      $configurations = array(
        ["type" => "lastXWeeksDate",     "expected" => ["AND" => ["dateEnd>=" => $startLcl->format("Y-m-d"),       "dateEnd<"  => $endLcl->format("Y-m-d")]],  "value" => $i],
        ["type" => "lastXWeeksDatetime",    "expected" => ["AND" => ["dateEnd>=" => $startUtc->format("Y-m-d H:i:s"), "dateEnd<"  => $endUtc->format("Y-m-d H:i:s")]],  "value" => $i],
      );

      $this->runConfigurations($configurations);
    }
  }

  protected function testNextXWeeks(): void {
    $today = new DateTime("today", $this->local);

    $dayOfWeek = intval($today->format('w'));
    $nextWeekStart = (clone $today)->modify("+7 days")->modify("-" . $dayOfWeek . " days");

    for($i = 0; $i < 5; $i++) {
      $startLcl = $this->core->getWeekStartEnd(anchor: $nextWeekStart, addlWeeks: 0)[0];
      $endLcl = $this->core->getWeekStartEnd(anchor: $nextWeekStart, addlWeeks: $i - 1)[1];

      $startUtc = $this->core->getWeekStartEnd(anchor: $nextWeekStart, addlWeeks: 0, returnUtc: true)[0];
      $endUtc = $this->core->getWeekStartEnd(anchor: $nextWeekStart, addlWeeks: $i - 1, returnUtc: true)[1];

      $configurations = array(
        ["type" => "nextXWeeksDate",     "expected" => ["AND" => ["dateEnd>=" => $startLcl->format("Y-m-d"),       "dateEnd<"  => $endLcl->format("Y-m-d")]],  "value" => $i],
        ["type" => "nextXWeeksDatetime",    "expected" => ["AND" => ["dateEnd>=" => $startUtc->format("Y-m-d H:i:s"), "dateEnd<"  => $endUtc->format("Y-m-d H:i:s")]],  "value" => $i],
      );

      $this->runConfigurations($configurations);
    }
  }
}
