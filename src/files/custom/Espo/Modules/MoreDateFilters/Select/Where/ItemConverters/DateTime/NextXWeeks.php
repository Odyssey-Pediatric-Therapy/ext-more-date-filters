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

class NextXWeeks implements ItemConverter
{
  public function __construct(
    private Config $config,
  ) {}

  public function convert(QueryBuilder $queryBuilder, Item $item): WhereClauseItem
  {
    $local = new DateTimeZone($this->config->get("timeZone", "America/Denver"));
    $today = new DateTime("today", $local);

    $utc = new DateTimeZone("UTC");
    $today->setTimezone($utc);

    $xValue = $item->getValue();
    $days = intval($xValue) * 7;

    $dayOfWeek = intval($today->format('w'));
    $from = (clone $today)->modify("+7 days")->modify("-" . $dayOfWeek . " days");
    $to = (clone $from)->modify("+{$days} days");

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
