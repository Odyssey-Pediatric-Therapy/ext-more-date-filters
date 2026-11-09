<?php

namespace Espo\Modules\MoreDateFilters\Select\Where;

use DateTime;
use DateTimeZone;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Select\Where\Item;
use Espo\Core\Select\Where\ItemConverter;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Modules\MoreDateFilters\Services\Core;
use Espo\ORM\Query\Part\WhereClause;
use Espo\ORM\Query\Part\WhereItem;
use Espo\ORM\Query\SelectBuilder;

/** Shared implementation for the extension's registered range converters. */
class RangeConverter implements ItemConverter
{
    public const MAX_COUNT = 10000;

    // These filters use X as a week-boundary offset, rather than a range length.
    public const ZERO_ALLOWED_TYPES = [
        'upToNextXWeeksExt',
        'upToNextXWeeksFutureExt',
        'afterNextXWeeksExt',
        'beforeXWeeksAgoExt',
        'afterXWeeksAgoExt',
    ];

    public function __construct(
        private string $entityType,
        private Config $config,
        private Core $core,
        private Metadata $metadata,
    ) {}

    public function convert(SelectBuilder $queryBuilder, Item $item): WhereItem
    {
        $attribute = $item->getAttribute();
        if (!$attribute) {
            throw new BadRequest('Date filter requires an attribute.');
        }

        $type = $item->getType();
        $x = str_contains($type, 'X') ? $this->count($item->getValue(), $type) : 1;
        $fieldType = $this->fieldType($this->entityType, $attribute);
        if (!in_array($fieldType, ['date', 'datetime', 'datetimeOptional'], true)) {
            throw new BadRequest('Date filter requires a date or datetime field.');
        }

        $data = $item->getData();
        $timezoneName = $data instanceof Item\Data\Date || $data instanceof Item\Data\DateTime ?
            $data->getTimeZone() : null;
        $timezoneName ??= $this->config->get('timeZone') ?: 'UTC';
        if (!is_string($timezoneName)) {
            throw new BadRequest('Invalid date filter timezone.');
        }
        try {
            $timezone = new DateTimeZone($timezoneName);
        } catch (\Exception $e) {
            throw new BadRequest('Invalid date filter timezone.', 0, $e);
        }

        $now = $this->core->now($timezone);
        $weekStart = (int) $this->config->get('weekStart', 0);
        $weekStart = (($weekStart % 7) + 7) % 7;
        $where = $this->clause($attribute, $type, $now, $x, $weekStart, $fieldType === 'date');

        // Optional datetime fields have a date-only companion for all-day values.
        // Keep the two representations disjoint, as Espo's CRM transformer does.
        if ($fieldType === 'datetimeOptional') {
            $dateAttribute = $this->dateCompanion($this->entityType, $attribute);
            if (preg_match('/(?:Minute|Hour)/', $type)) {
                return WhereClause::fromRaw(['AND' => [$where, [$dateAttribute => null]]]);
            }
            $where = ['OR' => [
                $this->clause($dateAttribute, $type, $now, $x, $weekStart, true),
                ['AND' => [$where, [$dateAttribute => null]]],
            ]];
        }

        return WhereClause::fromRaw($where);
    }

    private function count(mixed $value, string $type): int
    {
        $minimum = in_array($type, self::ZERO_ALLOWED_TYPES, true) ? 0 : 1;
        if ((!is_int($value) && !is_string($value)) ||
            !preg_match('/^(?:0|[1-9][0-9]*)$/D', (string) $value) ||
            (int) $value < $minimum ||
            strlen((string) $value) > 5 || (int) $value > self::MAX_COUNT) {
            throw new BadRequest('Date filter count must be an integer between ' . $minimum . ' and ' . self::MAX_COUNT . '.');
        }
        return (int) $value;
    }

    /** @return array<string, mixed> */
    private function clause(string $attribute, string $type, DateTime $now, int $x, int $weekStart, bool $dateOnly): array
    {
        [$from, $to] = $this->core->range($type, $now, $x, $weekStart, $dateOnly);
        $format = $dateOnly ? 'Y-m-d' : 'Y-m-d H:i:s';
        // Dates outside the database-supported calendar should fail before query execution.
        foreach ([$from, $to] as $boundary) {
            if ($boundary && ((int) $boundary->format('Y') < 1 || (int) $boundary->format('Y') > 9999)) {
                throw new BadRequest('Date filter range is outside the supported calendar.');
            }
        }
        $conditions = [];
        if ($from) $conditions[$attribute . '>='] = $from->format($format);
        if ($to) $conditions[$attribute . '<'] = $to->format($format);
        return ['AND' => $conditions];
    }

    private function dateCompanion(string $entityType, string $attribute): string
    {
        if (str_contains($attribute, '.')) {
            [$link, $field] = explode('.', $attribute, 2);
            /** @var string $related Already checked by fieldType. */
            $related = $this->metadata->get(['entityDefs', $entityType, 'links', $link, 'entity']);
            return $link . '.' . $this->dateCompanion($related, $field);
        }
        $defs = $this->metadata->get(['entityDefs', $entityType, 'fields', $attribute], []);
        if (is_array($defs) && ($defs['type'] ?? null) === 'foreign') {
            return $this->dateCompanion($entityType, $defs['link'] . '.' . $defs['field']);
        }
        return $attribute . 'Date';
    }

    private function fieldType(string $entityType, string $attribute, int $depth = 0): string
    {
        if ($depth > 8) {
            throw new BadRequest('Circular or unsupported foreign date field.');
        }
        if (str_contains($attribute, '.')) {
            [$link, $field] = explode('.', $attribute, 2);
            $related = $this->metadata->get(['entityDefs', $entityType, 'links', $link, 'entity']);
            if (!is_string($related) || !$related) {
                throw new BadRequest('Unknown date field relationship.');
            }
            return $this->fieldType($related, $field, $depth + 1);
        }
        $defs = $this->metadata->get(['entityDefs', $entityType, 'fields', $attribute], []);
        if (!is_array($defs) || !is_string($defs['type'] ?? null)) {
            throw new BadRequest('Unknown date field.');
        }
        if ($defs['type'] === 'foreign') {
            $link = $defs['link'] ?? null;
            $field = $defs['field'] ?? null;
            if (!is_string($link) || !is_string($field)) {
                throw new BadRequest('Invalid foreign date field.');
            }
            return $this->fieldType($entityType, "$link.$field", $depth + 1);
        }
        return $defs['type'];
    }
}
