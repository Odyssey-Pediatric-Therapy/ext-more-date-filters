<?php

namespace Espo\Modules\MoreDateFilters\Services;

use DateTime;
use DateTimeZone;
use DateInterval;

class Core
{
  /** @return array{DateTime, DateTime} */
  private function convert(DateTime $from, DateTime $to): array {
    $from->setTimezone(new DateTimeZone('UTC'));
    $to->setTimezone(new DateTimeZone('UTC'));

    return [$from, $to];
  }

  /** @return array{DateTime, DateTime} */
  public function getFullMinute(DateTime $anchor, int $addlMinutes = 0): array {
    $from = clone $anchor;
    $from->setTimestamp($from->getTimestamp() - (int) $from->format('s'));

    $to = clone $from;
    $minutes = 1 + $addlMinutes;
    #DateInterval instead of modify to avoid DST gaps
    $to->add(new DateInterval("PT{$minutes}M"));

    return $this->convert($from, $to);
  }

  /** @return array{DateTime, DateTime} */
  public function getFullHour(DateTime $anchor, int $addlHours = 0): array {
    $from = clone $anchor;
    $from->setTimestamp($from->getTimestamp() - (int) $from->format('i') * 60 - (int) $from->format('s'));

    $to = clone $from;
    $hours = 1 + $addlHours;
    #DateInterval instead of modify to avoid DST gaps
    $to->add(new DateInterval("PT{$hours}H"));

    return $this->convert($from, $to);
  }

  /** @return array{DateTime, DateTime} */
  public function getFullDay(DateTime $anchor, int $addlDays = 0): array {
    $from = clone $anchor;
    $from->setTime(0, 0);

    $to = clone $from;
    $days = 1 + $addlDays;
    $to->modify("+{$days} day");

    return $this->convert($from, $to);
  }

  /** @return array{DateTime, DateTime} */
  public function getWeekStartEnd(DateTime $anchor, int $addlWeeks = 0, int $weekStart = 0): array {
    $from = clone $anchor;
    $from->setTime(0, 0);

    $dayOfWeek = ((int) $anchor->format('w') - $weekStart + 7) % 7;
    $from->modify("-" . $dayOfWeek . " days");

    $to = clone $from;
    $days = (1 + $addlWeeks) * 7;
    $to->modify("+{$days} day");

    return $this->convert($from, $to);
  }

  /** @return array{DateTime, DateTime} */
  public function getFullMonth(DateTime $anchor, int $addlMonths = 0): array {
    $from = clone $anchor;
    $from->modify("first day of this month")->setTime(0, 0);

    $to = clone $from;
    $months = 1 + $addlMonths;
    $to->modify("+{$months} month");

    return $this->convert($from, $to);
  }

  /** @return array{DateTime, DateTime} */
  public function getFullQuarter(DateTime $anchor, int $addlQuarters = 0): array {
    $quarter = ceil($anchor->format("n") / 3);

    $from = clone $anchor;
    $from->setDate((int) $anchor->format("Y"), (int) ($quarter * 3 - 2), 1)->setTime(0, 0);

    $months = (1 + $addlQuarters) * 3;
    $to = (clone $from)->modify("+{$months} months");

    return $this->convert($from, $to);
  }

  /** @return array{DateTime, DateTime} */
  public function getFullYear(DateTime $anchor, int $addlYears = 0): array {
    $from = clone $anchor;
    $from->modify("first day of January this year")->setTime(0, 0);

    $to = clone $from;
    $years = 1 + $addlYears;
    $to->modify("+{$years} year");

    return $this->convert($from, $to);
  }
  public function now(DateTimeZone $timezone): DateTime
  {
    return new DateTime('now', $timezone);
  }

  /**
   * Calendar dates are represented in UTC without changing their calendar day.
   * Datetimes are calculated locally and converted to UTC only at the end.
   * @return array{?DateTime, ?DateTime} Inclusive lower, exclusive upper boundary.
   */
  public function range(string $type, DateTime $now, int $x = 1, int $weekStart = 0, bool $dateOnly = false): array
  {
    $now = $dateOnly ? new DateTime($now->format('Y-m-d'), new DateTimeZone('UTC')) : clone $now;
    $start = clone $now;
    $unit = null;

    if (preg_match('/(?:Minute|Minutes)/', $type)) $unit = 'minute';
    elseif (preg_match('/(?:Hour|Hours)/', $type)) $unit = 'hour';
    elseif (preg_match('/(?:Week|Weeks)/', $type)) $unit = 'week';
    elseif (preg_match('/(?:Month|Months)/', $type)) $unit = 'month';
    elseif (preg_match('/(?:Quarter|Quarters)/', $type)) $unit = 'quarter';
    elseif (preg_match('/(?:Year|Years)/', $type)) $unit = 'year';
    else $unit = 'day';

    if ($dateOnly && in_array($unit, ['minute', 'hour'], true)) {
      throw new \Espo\Core\Exceptions\BadRequest('Sub-day filters require a datetime field.');
    }

    switch ($unit) {
      case 'minute':
        // Truncate elapsed seconds, preserving which occurrence of a repeated DST hour this is.
        $start->setTimestamp($start->getTimestamp() - (int) $start->format('s'));
        break;
      case 'hour':
        $start->setTimestamp($start->getTimestamp() - (int) $start->format('i') * 60 - (int) $start->format('s'));
        break;
      case 'day': $start->setTime(0, 0); break;
      case 'week':
        $offset = ((int) $start->format('w') - $weekStart + 7) % 7;
        $start->setTime(0, 0)->modify("-$offset days");
        break;
      case 'month': $start->modify('first day of this month')->setTime(0, 0); break;
      case 'quarter':
        $month = intdiv((int) $start->format('n') - 1, 3) * 3 + 1;
        $start->setDate((int) $start->format('Y'), $month, 1)->setTime(0, 0);
        break;
      case 'year': $start->setDate((int) $start->format('Y'), 1, 1)->setTime(0, 0); break;
    }

    $end = $this->shift($start, $unit, 1);
    $from = $start;
    $to = $end;

    if ($type === 'yesterdayExt') {
      $from = $this->shift($start, 'day', -1); $to = $start;
    } elseif ($type === 'tomorrowExt') {
      $from = $end; $to = $this->shift($end, 'day', 1);
    } elseif ($type === 'lastSevenDaysExt') {
      $from = $this->shift($start, 'day', -7); $to = $start;
    } elseif (str_starts_with($type, 'last')) {
      $count = str_starts_with($type, 'lastX') ? $x : 1;
      $from = $this->shift($start, $unit, -$count); $to = $start;
    } elseif (str_starts_with($type, 'next')) {
      $count = str_starts_with($type, 'nextX') ? $x : 1;
      $from = $end; $to = $this->shift($end, $unit, $count);
    } elseif ($type === 'afterNextXWeeksExt') {
      $from = $this->shift($end, 'week', $x); $to = null;
    } elseif (str_starts_with($type, 'upToNextXWeeks')) {
      $from = $type === 'upToNextXWeeksFutureExt' ? clone $now : null;
      $to = $this->shift($end, 'week', $x);
    } elseif ($type === 'beforeXWeeksAgoExt') {
      $from = null; $to = $this->shift($start, 'week', -$x);
    } elseif (str_starts_with($type, 'afterXWeeksAgo')) {
      $from = $this->shift($start, 'week', 1 - $x);
      $to = $type === 'afterXWeeksAgoPastExt' ? clone $now : null;
    } elseif (str_ends_with($type, 'PastExt')) {
      $to = clone $now;
    } elseif (str_ends_with($type, 'FutureExt')) {
      $from = clone $now;
    }

    foreach ([$from, $to] as $boundary) {
      if ($boundary) $boundary->setTimezone(new DateTimeZone('UTC'));
    }

    return [$from, $to];
  }

  private function shift(DateTime $anchor, string $unit, int $count): DateTime
  {
    $result = clone $anchor;
    if (in_array($unit, ['minute', 'hour'], true)) {
      $seconds = $count * ($unit === 'hour' ? 3600 : 60);
      $result->setTimestamp($result->getTimestamp() + $seconds);
      return $result;
    }
    if ($unit === 'quarter') { $unit = 'month'; $count *= 3; }
    $result->modify(sprintf('%+d %s', $count, $unit));
    return $result;
  }
}
