<?php

namespace Espo\Modules\MoreDateFilters\Select\Where\ItemConverters\DateTime;

use DateTime;
use DateTimeZone;

use Espo\Core\Select\Where\Item;
use Espo\Core\Select\Where\ItemConverter;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\ORM\Query\Part\WhereClause;
use Espo\ORM\Query\Part\WhereItem as WhereClauseItem;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;

class NextQuarter implements ItemConverter
{
  public function __construct(
    private Config $config,
  ) {}

  public function convert(QueryBuilder $queryBuilder, Item $item): WhereClauseItem
  {
    $local = new DateTimeZone($this->config->get("timeZone", "America/Denver"));
    $anchor = new DateTime("+3 months", $local);

    $quarter = ceil($anchor->format("n") / 3);
    $from = (new DateTime($anchor->format("Y-") . ($quarter * 3 - 2) . "-1", $local))->setTime(0, 0);

    $utc = new DateTimeZone("UTC");
    $from->setTimezone($utc);

    $to = (clone $from)->modify("+3 months");

    $attribute = $item->getAttribute();
    $where = [
      'AND' => [
        $attribute . '>=' => $from->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT),
        $attribute . '<'  => $to->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT),
      ],
    ];

    return WhereClause::fromRaw($where);
  }
}
