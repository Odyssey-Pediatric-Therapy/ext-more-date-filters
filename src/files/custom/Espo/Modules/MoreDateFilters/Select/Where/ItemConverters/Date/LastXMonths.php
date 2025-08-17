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

class LastXMonths implements ItemConverter
{
  public function __construct(
    private Config $config,
  ) {}

  public function convert(QueryBuilder $queryBuilder, Item $item): WhereClauseItem
  {
    $local = new DateTimeZone($this->config->get("timeZone", "America/Denver"));
    $today = new DateTime("today", $local);

    $xValue = intval($item->getValue());

    $from = (clone $today)->modify("-{$xValue} months")->modify('first day of this month');
    $to = (clone $today)->modify('first day of this month');

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
