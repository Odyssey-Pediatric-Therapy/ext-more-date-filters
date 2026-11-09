<?php

namespace Espo\Modules\MoreDateFilters\Classes\Metadata;

use Espo\Core\Select\Where\DefaultDateTimeItemTransformer;
use Espo\Core\Utils\Metadata\AdditionalBuilder;
use Espo\Modules\MoreDateFilters\Select\Where\DateTimeItemTransformer;
use stdClass;

class DateFilters implements AdditionalBuilder
{
    public function build(stdClass $data): void
    {
        // Replace only known native views. Explicit third-party/custom views keep priority.
        $this->replaceViews($data);
        $data->selectDefs ??= new stdClass();
        foreach (get_object_vars($data->entityDefs ?? new stdClass()) as $entityType => $defs) {
            $data->selectDefs->$entityType ??= new stdClass();
            $select = $data->selectDefs->$entityType;
            if (($select->whereDateTimeItemTransformerClassName ?? null) === DateTimeItemTransformer::class) {
                continue;
            }
            $select->moreDateFiltersOriginalTransformer =
                $select->whereDateTimeItemTransformerClassName ?? DefaultDateTimeItemTransformer::class;
            $select->whereDateTimeItemTransformerClassName = DateTimeItemTransformer::class;
        }
    }

    private function replaceViews(stdClass $node): void
    {
        $views = [
            'views/fields/date' => 'fields/date',
            'views/fields/datetime' => 'fields/datetime',
            'views/fields/datetime-optional' => 'fields/datetime-optional',
            'views/fields/datetime-short' => 'fields/datetime-short',
            'views/fields/foreign-date' => 'fields/foreign-date',
            'views/fields/foreign-datetime' => 'fields/foreign-datetime',
            'views/working-time-range/fields/date-end' => 'working-time-range/fields/date-end',
        ];
        foreach (get_object_vars($node) as $key => $value) {
            if (is_object($value) && $value instanceof stdClass) {
                $this->replaceViews($value);
            } elseif ($key === 'view' && is_string($value) && isset($views[$value])) {
                $node->$key = 'more-date-filters:views/' . $views[$value];
            }
        }
    }
}
