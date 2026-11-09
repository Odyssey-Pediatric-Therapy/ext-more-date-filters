<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Espo\Modules\MoreDateFilters\Services\Core;
use Generator;
use Faker\Factory;
use Faker\Generator as FakerGenerator;
use InvalidArgumentException;

if (!class_exists(Factory::class)) {
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new \RuntimeException('Install test dependencies first: composer install --working-dir=tests');
    }
    require_once $autoload;
}

/** Test-only fixture planner. Does not connect to a database. */
final class DateFilterSeed
{
    public readonly DateTimeImmutable $from;
    public readonly DateTimeImmutable $to;
    private DateTimeImmutable $now;
    private FakerGenerator $faker;
    public readonly string $startTime;
    public readonly string $endTime;
    private int $startMinutes;
    private int $endMinutes;

    public function __construct(
        DateTimeImmutable $now,
        private string $runId,
        private int $count = 2000,
        private int $weekStart = 0,
        private int $allDayPercent = 3,
        ?FakerGenerator $faker = null,
        string $startTime = '0800',
        string $endTime = '1800',
    ) {
        if ($count < 2000 || $count > 100000) {
            throw new InvalidArgumentException('Count must be between 2000 and 100000 per entity.');
        }
        if (!preg_match('/^[a-zA-Z0-9_-]{1,64}$/D', $runId)) {
            throw new InvalidArgumentException('Run ID must contain 1–64 letters, numbers, underscores or hyphens.');
        }
        if ($weekStart < 0 || $weekStart > 6) {
            throw new InvalidArgumentException('Week start must be 0 (Sunday) through 6 (Saturday).');
        }
        if ($allDayPercent < 0 || $allDayPercent > 10) {
            throw new InvalidArgumentException('All-day percentage must be between 0 and 10.');
        }
        $this->startMinutes = self::parseTime($startTime);
        $this->endMinutes = self::parseTime($endTime);
        if ($this->endMinutes <= $this->startMinutes) {
            throw new InvalidArgumentException('End time must be later than start time on the same local day.');
        }
        $this->startTime = sprintf('%02d:%02d', intdiv($this->startMinutes, 60), $this->startMinutes % 60);
        $this->endTime = sprintf('%02d:%02d', intdiv($this->endMinutes, 60), $this->endMinutes % 60);
        $this->faker = $faker ?? Factory::create('en_US');
        if ($faker === null) $this->faker->seed(random_int(0, 2147483647));
        $this->now = $now->setTimestamp($now->getTimestamp());
        $this->from = $this->yearOffset(-2);
        $this->to = $this->yearOffset(2);
    }

    /** @return Generator<array{entityType: string, attributes: array<string, mixed>}> */
    public function records(): Generator
    {
        $usedCounts = [];
        foreach (['Task', 'Meeting', 'Call'] as $entityType) {
            // Counts and dates vary independently for each type, in addition to each run.
            do {
                $spreadCount = $this->count + $this->faker->numberBetween(0, max(500, intdiv($this->count, 4)));
            } while (isset($usedCounts[$spreadCount]));
            $usedCounts[$spreadCount] = true;
            $anchors = $this->anchors($spreadCount);
            $index = 0;
            $special = array_values(array_filter($anchors, fn ($anchor) => $anchor[1] !== 'spread'));
            usort($special, fn ($a, $b) => $a[0] <=> $b[0]);
            $ends = $coveredEnds = [];
            for ($i = 0; $i + 1 < count($special); $i++) {
                [$start] = $special[$i];
                [$end] = $special[$i + 1];
                if ($end->getTimestamp() - $start->getTimestamp() <= 7200) {
                    $ends[$start->getTimestamp()] = $end;
                    $coveredEnds[$end->getTimestamp()] = true;
                }
            }
            // Keep one short event crossing each actual clock change, rather than only
            // ending/starting exactly at the transition.
            foreach ($special as [$anchor, $scenario]) {
                if (str_starts_with($scenario, 'dst:') && str_ends_with($scenario, ':-1')) {
                    $end = $anchor->setTimestamp($anchor->getTimestamp() + 2);
                    if ($end <= $this->to) $ends[$anchor->getTimestamp()] = $end;
                }
            }
            $coveredEnds = [];
            foreach ($ends as $end) $coveredEnds[$end->getTimestamp()] = true;
            foreach ($anchors as [$anchor, $scenario]) {
                yield ['entityType' => $entityType, 'attributes' => $this->timed($entityType, ++$index, $anchor, $scenario, false, $ends[$anchor->getTimestamp()] ?? null)];
                if ($scenario !== 'spread' && $anchor > $this->from && !isset($coveredEnds[$anchor->getTimestamp()])) {
                    yield ['entityType' => $entityType, 'attributes' => $this->timed($entityType, ++$index, $anchor, "end-target:$scenario", true)];
                }
            }
            // A small sample of distinct days, rather than copying every boundary as all-day.
            if ($entityType !== 'Call' && $this->allDayPercent > 0) {
                $target = (int) round($index * $this->allDayPercent / (100 - $this->allDayPercent));
                foreach ($this->allDayAnchors($anchors, $target) as $start) {
                    $end = $start->modify('+1 day');
                    $attributes = $this->timed($entityType, ++$index, $start, 'all-day');
                    $attributes['dateStartDate'] = $start->format('Y-m-d');
                    $attributes['dateEndDate'] = $start->format('Y-m-d');
                    $attributes['dateStart'] = $this->utc($start);
                    $attributes['dateEnd'] = $this->utc($end);
                    if ($entityType === 'Meeting') {
                        $attributes['isAllDay'] = true;
                        if ($end > $this->now) $attributes['status'] = 'Planned';
                    }
                    if ($entityType === 'Task' && $attributes['status'] === 'Completed') {
                        $attributes['dateCompleted'] = $end <= $this->now ? $this->utc($end) : null;
                        if ($end > $this->now) $attributes['status'] = 'Started';
                    }
                    yield ['entityType' => $entityType, 'attributes' => $attributes];
                }
            }
            // Calls/meetings require dates; undated Tasks provide valid is-empty fixtures.
            if ($entityType === 'Task') {
                for ($i = 0; $i < 20; $i++) {
                    $anchor = $this->now->setTimestamp($this->faker->numberBetween($this->from->getTimestamp(), $this->to->getTimestamp()));
                    $attributes = $this->timed($entityType, ++$index, $anchor, 'undated');
                    foreach (['dateStart', 'dateEnd', 'dateCompleted'] as $field) $attributes[$field] = null;
                    $attributes['status'] = 'Not Started';
                    yield ['entityType' => $entityType, 'attributes' => $attributes];
                }
            }
        }
    }

    /** @return list<array{DateTimeImmutable, string}> */
    private function anchors(int $count): array
    {
        $anchors = [];
        $span = $this->to->getTimestamp() - $this->from->getTimestamp();
        for ($i = 0; $i < $count; $i++) {
            $lower = $this->from->getTimestamp() + (int) floor($span * $i / ($count - 1));
            $upper = $this->from->getTimestamp() + (int) floor($span * ($i + 1) / ($count - 1)) - 1;
            if ($i === 0 || $i === $count - 1) {
                // Preserve the exact overall range endpoints as explicit boundary fixtures.
                $anchors[] = [$this->now->setTimestamp($lower), 'span-boundary'];
                continue;
            }
            $anchor = $this->now->setTimestamp($this->faker->numberBetween($lower, $upper));
            // Today already has the compact minute/hour examples; do not add random extras.
            if ($anchor->format('Y-m-d') === $this->now->format('Y-m-d')) continue;
            $window = $this->dailyWindow($anchor);
            if ($window === null) continue;
            [$opening, $closing] = $window;
            $anchor = $anchor->setTimestamp($this->faker->numberBetween($opening->getTimestamp(), $closing->getTimestamp() - 1));
            $anchors[] = [$anchor, 'spread'];
        }
        // One small set of points covers overlapping filter windows. Do not manufacture
        // one record per minute or copy every filter boundary onto the current day.
        $metadataPath = dirname(__DIR__, 2) . '/src/files/custom/Espo/Modules/MoreDateFilters/Resources/metadata/app/select.json';
        $types = array_keys(json_decode(file_get_contents($metadataPath), true, 512, JSON_THROW_ON_ERROR)['whereItemConverterClassNameMap']);
        $types = array_merge($types, ['currentMonth', 'lastMonth', 'nextMonth', 'currentQuarter', 'lastQuarter', 'currentYear', 'lastYear']);
        $core = new Core();
        $windows = [];
        foreach ($types as $type) {
            foreach (str_contains($type, 'X') ? [1, 2] : [1] as $x) {
                [$from, $to] = $core->range($type, DateTime::createFromImmutable($this->now), $x, $this->weekStart);
                $lower = max($from?->getTimestamp() ?? $this->from->getTimestamp(), $this->from->getTimestamp());
                $upper = min($to?->getTimestamp() ?? $this->to->getTimestamp() + 1, $this->to->getTimestamp() + 1);
                if ($lower < $upper) $windows[] = [$lower, $upper, "filter-window:$type:x$x"];
            }
        }
        usort($windows, fn ($a, $b) => $a[1] <=> $b[1]);
        $point = null;
        foreach ($windows as [$lower, $upper, $scenario]) {
            if ($point !== null && $point >= $lower) continue;
            $point = $upper - min(2, $upper - $lower);
            $anchors[] = [$this->now->setTimestamp($point), $scenario];
        }
        array_push($anchors, ...$this->calendarAnchors());
        // Many filters share boundaries. Keep the required timestamp just once.
        $unique = [];
        foreach ($anchors as $anchor) $unique[$anchor[0]->getTimestamp()] = $anchor;
        return array_values($unique);
    }

    /** @return list<array{DateTimeImmutable, string}> */
    private function calendarAnchors(): array
    {
        $anchors = [];
        $add = function (DateTimeImmutable $anchor, string $scenario) use (&$anchors): void {
            if ($anchor >= $this->from && $anchor <= $this->to) $anchors[] = [$anchor, $scenario];
        };
        // Use the selected timezone's real transition table, including non-hour shifts.
        // The first row describes the starting offset; it is not itself a transition.
        $transitions = $this->now->getTimezone()->getTransitions($this->from->getTimestamp() - 1, $this->to->getTimestamp() + 1);
        $previousOffset = null;
        foreach ($transitions ?: [] as $transition) {
            $offset = $transition['offset'];
            if ($previousOffset !== null && $offset !== $previousOffset) {
                $delta = $offset - $previousOffset;
                $direction = $delta > 0 ? 'forward' : 'backward';
                $halfShift = max(1, intdiv(abs($delta), 2));
                foreach (array_unique([-$halfShift, -1, 0, 1, $halfShift]) as $seconds) {
                    $anchor = $this->now->setTimestamp($transition['ts'] + $seconds);
                    $add($anchor, "dst:$direction:{$transition['ts']}:$seconds");
                }
            }
            $previousOffset = $offset;
        }
        for ($year = (int) $this->from->format('Y'); $year <= (int) $this->to->format('Y'); $year++) {
            if (!checkdate(2, 29, $year)) continue;
            $leapDay = $this->now->setDate($year, 2, 29)->setTime(0, 0);
            foreach ([$leapDay, $leapDay->modify('+1 day')] as $midnight) {
                foreach ([-1, 0, 1] as $seconds) {
                    $add($midnight->setTimestamp($midnight->getTimestamp() + $seconds), "leap-day:$year:boundary:$seconds");
                }
            }
            $add($leapDay->setTime(12, 0), "leap-day:$year:noon");
        }
        return $anchors;
    }

    /** @param list<array{DateTimeImmutable, string}> $anchors
     * @return list<DateTimeImmutable>
     */
    private function allDayAnchors(array $anchors, int $target): array
    {
        $days = [];
        foreach ($anchors as [$anchor]) {
            $day = $anchor->setTime(0, 0);
            if ($day >= $this->from && $day->modify('+1 day') <= $this->to) {
                $days[$day->format('Y-m-d')] = $day;
            }
        }
        $selected = [];
        foreach ([-1, 0, 1] as $offset) {
            $day = $this->now->setTime(0, 0)->modify(sprintf('%+d days', $offset));
            if (isset($days[$day->format('Y-m-d')])) $selected[$day->format('Y-m-d')] = $day;
        }
        // Keep all-day coverage of DST and leap-day calendar dates within the same quota.
        foreach ($anchors as [$anchor, $scenario]) {
            if (!str_starts_with($scenario, 'dst:') && !str_starts_with($scenario, 'leap-day:')) continue;
            $key = $anchor->format('Y-m-d');
            if (isset($days[$key])) $selected[$key] = $days[$key];
        }
        foreach ($this->faker->shuffleArray(array_values($days)) as $day) {
            if (count($selected) >= $target) break;
            $selected[$day->format('Y-m-d')] = $day;
        }
        $result = array_slice(array_values($selected), 0, $target);
        // Very large batches can need more all-day records than there are distinct days.
        while (count($result) < $target && $days !== []) {
            $result[] = $this->faker->randomElement(array_values($days));
        }
        return $result;
    }

    private function title(string $entityType): string
    {
        $person = $this->faker->firstName() . ' ' . $this->faker->lastName();
        $company = $this->faker->company();
        $topic = $this->faker->randomElement(['budget', 'contract', 'project plan', 'product launch', 'account renewal', 'sales forecast', 'onboarding']);
        return match ($entityType) {
            'Task' => $this->faker->randomElement([
                "Prepare $topic for $company", "Review $topic with $person", "Follow up with $person at $company",
                "Send $topic update to $person", "Finalize $topic for $company",
            ]),
            'Meeting' => $this->faker->randomElement([
                ucfirst($topic) . " review with $company", "Planning session with $person",
                "Project kickoff with $company", ucfirst($topic) . " discussion with $person",
                "Quarterly check-in with $company",
            ]),
            'Call' => $this->faker->randomElement([
                "Call $person about the $topic", "Follow-up call with $company", "Check in with $person",
                "Discuss $topic with $company", "Catch up with $person at $company",
            ]),
        };
    }

    /** @return array<string, mixed> */
    private function timed(string $entityType, int $index, DateTimeImmutable $anchor, string $scenario, bool $endAtAnchor = false, ?DateTimeImmutable $explicitEnd = null): array
    {
        $duration = $entityType === 'Call' ? $this->faker->numberBetween(5, 30) * 60 : $this->faker->numberBetween(1, 8) * 900;
        $end = $explicitEnd ?? $anchor->setTimestamp(min($anchor->getTimestamp() + $duration, $this->to->getTimestamp()));
        if ($scenario === 'spread') {
            $window = $this->dailyWindow($anchor);
            if ($window === null) throw new \LogicException('Spread event has no valid daily time window.');
            $end = $end->setTimestamp(min($end->getTimestamp(), $window[1]->getTimestamp()));
        }
        $start = $end > $anchor ? $anchor : $anchor->modify('-1 second');
        if ($endAtAnchor) {
            $end = $anchor;
            $start = $anchor->setTimestamp(max($anchor->getTimestamp() - $duration, $this->from->getTimestamp()));
        }
        if (!$endAtAnchor && $explicitEnd === null && $end->format('Y-m-d') !== $anchor->format('Y-m-d')) {
            $dayEnd = $anchor->modify('+1 day')->setTime(0, 0)->modify('-1 second');
            if ($dayEnd > $anchor && $dayEnd <= $this->to) $end = $dayEnd;
        }
        $past = $end <= $this->now;
        $attributes = [
            'name' => $this->title($entityType),
            'description' => "More Date Filters test fixture. Run: {$this->runId}. Scenario: $scenario. Anchor: " . $this->now->format('c'),
            'dateStart' => $this->utc($start),
            'dateEnd' => $this->utc($end),
            'createdAt' => $this->utc($anchor),
            'modifiedAt' => $this->utc($anchor),
        ];
        if ($entityType === 'Task') {
            $attributes['status'] = $past ? ($index % 4 === 0 ? 'Canceled' : 'Completed') : ($index % 3 === 0 ? 'Started' : 'Not Started');
            $attributes['priority'] = ['Low', 'Normal', 'High', 'Urgent'][$index % 4];
            $attributes['dateCompleted'] = $attributes['status'] === 'Completed' ? $this->utc($end) : null;
            $attributes['dateStartDate'] = $attributes['dateEndDate'] = null;
        } else {
            $attributes['status'] = $past ? ($index % 4 === 0 ? 'Not Held' : 'Held') : 'Planned';
            if ($entityType === 'Call') $attributes['direction'] = $index % 2 ? 'Outbound' : 'Inbound';
            else {
                $attributes['isAllDay'] = false;
                $attributes['dateStartDate'] = $attributes['dateEndDate'] = null;
            }
        }
        return $attributes;
    }

    private static function parseTime(string $value): int
    {
        if (!preg_match('/^([01][0-9]|2[0-3]):?([0-5][0-9])$/D', $value, $match)) {
            throw new InvalidArgumentException('Times must use HHMM or HH:MM in 24-hour local time (for example, 0800 or 18:00).');
        }
        return (int) $match[1] * 60 + (int) $match[2];
    }

    /** @return ?array{DateTimeImmutable, DateTimeImmutable} */
    private function dailyWindow(DateTimeImmutable $day): ?array
    {
        $opening = $day->setTime(intdiv($this->startMinutes, 60), $this->startMinutes % 60);
        $closing = $day->setTime(intdiv($this->endMinutes, 60), $this->endMinutes % 60);
        // A local time inside a spring-forward gap does not exist. Do not silently move it.
        if ($opening->format('H:i') !== $this->startTime || $closing->format('H:i') !== $this->endTime) return null;
        if ($opening < $this->from) $opening = $this->from;
        if ($closing > $this->to) $closing = $this->to;
        return $opening < $closing ? [$opening, $closing] : null;
    }

    private function yearOffset(int $years): DateTimeImmutable
    {
        $year = (int) $this->now->format('Y') + $years;
        $first = $this->now->setDate($year, (int) $this->now->format('n'), 1);
        return $first->setDate($year, (int) $this->now->format('n'), min((int) $this->now->format('j'), (int) $first->format('t')));
    }

    private function utc(DateTimeImmutable $value): string
    {
        return $value->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
}
