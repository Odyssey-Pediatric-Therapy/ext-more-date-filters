<?php

namespace Espo\Modules\MoreDateFilters\Select\Where\ItemConverters;

use DateTime;
use DateTimeZone;

use Espo\Core\Select\Where\Item;
use Espo\Core\Select\Where\ItemConverter;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\ORM\Query\Part\WhereClause;
use Espo\ORM\Query\Part\WhereItem as WhereClauseItem;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;
use Espo\ORM\Type\AttributeType;

use Espo\Modules\MoreDateFilters\Services\Core;

class NextXQuarters implements ItemConverter
{
  public function __construct(
    private string $entityType,
    private Config $config,
    private Core $core,
    private Log $log,
    private Metadata $metadata,
  ) {}

  public function convert(QueryBuilder $queryBuilder, Item $item): WhereClauseItem
  {
    $where = [
      "id" => null,
    ];

    return WhereClause::fromRaw($where);
  }
}
