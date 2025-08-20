<?php

namespace Espo\Modules\MoreDateFilters\Select\Where\ItemConverters\DateTime;

use DateTime;
use DateTimeZone;

use Espo\Core\Select\Where\Item;
use Espo\Core\Select\Where\ItemConverter;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\ORM\Query\Part\WhereClause;
use Espo\ORM\Query\Part\WhereItem as WhereClauseItem;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;

use Espo\Modules\MoreDateFilters\Services\Core;

class Tomorrow implements ItemConverter
{
  public function __construct(
    private string $entityType,
    private Config $config,
    private Core $core,
    private Metadata $metadata,
  ) {}

  public function convert(QueryBuilder $queryBuilder, Item $item): WhereClauseItem
  {
    $local = new DateTimeZone($this->config->get("timeZone", "America/Denver"));
    $tomorrow = new DateTime("tomorrow", $local);

    list($from, $to) = $this->core->getFullDay(anchor: $tomorrow);

    $attribute = $item->getAttribute();
    $fieldDefs = $this->metadata->get(['entityDefs', $this->entityType, 'fields', $attribute], []);
    $fieldType = $fieldDefs['type'] ?? 'datetime';

    if($fieldType === 'date')
      $format = DateTimeUtil::SYSTEM_DATE_FORMAT;
    else
      $format = DateTimeUtil::SYSTEM_DATE_TIME_FORMAT;

    $where = [
      'AND' => [
        $attribute . '>=' => $from->format($format),
        $attribute . '<'  => $to->format($format),
      ],
    ];

    return WhereClause::fromRaw($where);
  }
}
