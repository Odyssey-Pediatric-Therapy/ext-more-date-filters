<?php

namespace Espo\Modules\MoreDateFilters\Select\Where\ItemConverters\Date;

use DateTime;
use DateTimeZone;

use Espo\Core\Select\Where\Item;
use Espo\Core\Select\Where\ItemConverter;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\ORM\Query\Part\WhereClause;
use Espo\ORM\Query\Part\WhereItem as WhereClauseItem;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;

use Espo\Modules\MoreDateFilters\Services\Core;

class LastWeek implements ItemConverter
{
  public function __construct(
    private Config $config,
    private Core $core,
  ) {}

  public function convert(QueryBuilder $queryBuilder, Item $item): WhereClauseItem
  {
    $local = new DateTimeZone($this->config->get("timeZone", "America/Denver"));
    $today = new DateTime("today", $local);

    $dayOfWeek = intval($today->format('w'));
    $lastWeekStart = (clone $today)->modify("-7 days")->modify("-" . $dayOfWeek . " days");
    
    list($from, $to) = $this->core->getWeekStartEnd(anchor: $lastWeekStart);

    $attribute = $item->getAttribute();
    $where = [
      'AND' => [
        $attribute . '>=' => $from->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
        $attribute . '<'  => $to->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
      ],
    ];

    return WhereClause::fromRaw($where);
  }
}
