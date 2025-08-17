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

class TestDays extends Base implements TestInterface
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
      "testTomorrow",
      "testYesterday",
    ];
  }

  protected function testTomorrow(): void {
    $tomorrow = new DateTime("tomorrow", $this->local);
    list($startLcl, $endLcl) = $this->core->getFullDay(anchor: $tomorrow);
    list($startUtc, $endUtc) = $this->core->getFullDay(anchor: $tomorrow, returnUtc: true);

    $configurations = array(
      ["type" => "tomorrowDate",     "expected" => ["AND" => ["dateEnd>=" => $startLcl->format("Y-m-d"),       "dateEnd<"  => $endLcl->format("Y-m-d")]]],
      ["type" => "tomorrowDatetime", "expected" => ["AND" => ["dateEnd>=" => $startUtc->format("Y-m-d H:i:s"), "dateEnd<"  => $endUtc->format("Y-m-d H:i:s")]]],
    );

    $this->runConfigurations($configurations);
  }

  protected function testYesterday(): void {
    $yesterday = new DateTime("yesterday", $this->local);
    list($startLcl, $endLcl) = $this->core->getFullDay(anchor: $yesterday);
    list($startUtc, $endUtc) = $this->core->getFullDay(anchor: $yesterday, returnUtc: true);

    $configurations = array(
      ["type" => "yesterdayDate",     "expected" => ["AND" => ["dateEnd>=" => $startLcl->format("Y-m-d"),       "dateEnd<"  => $endLcl->format("Y-m-d")]]],
      ["type" => "yesterdayDatetime", "expected" => ["AND" => ["dateEnd>=" => $startUtc->format("Y-m-d H:i:s"), "dateEnd<"  => $endUtc->format("Y-m-d H:i:s")]]],
    );

    $this->runConfigurations($configurations);
  }
}
