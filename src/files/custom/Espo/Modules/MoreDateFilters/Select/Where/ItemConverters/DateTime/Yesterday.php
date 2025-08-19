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

use Espo\Modules\MoreDateFilters\Services\Core;

class Yesterday implements ItemConverter
{
  public function __construct(
    private Config $config,
    private Core $core,
  ) {}

  public function convert(QueryBuilder $queryBuilder, Item $item): WhereClauseItem
  {
    $local = new DateTimeZone($this->config->get("timeZone", "America/Denver"));
    $yesterday = new DateTime("yesterday", $local);

    list($from, $to) = $this->core->getFullDay(anchor: $yesterday);

    $attribute = $item->getAttribute();
    $where = array(
      'AND' => array(
        $attribute . '>=' => $from->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT),
        $attribute . '<' => $to->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT),
      ),
    );

    return WhereClause::fromRaw($where);
  }
}
