<?php

namespace Espo\Modules\MoreDateFilters\Services;

use DateInterval;
use DateTime;
use DateTimeZone;

use Espo\Core\Utils\Log;

class Core
{
  public function __construct(
    private Log $log,
  ) {}

  private function convert(DateTime $from, DateTime $to): array {
    $from->setTimezone(new DateTimeZone('UTC'));
    $to->setTimezone(new DateTimeZone('UTC'));

    $this->log->debug("convert from: {$from->format('Y-m-d H:i:s')} to: {$to->format('Y-m-d H:i:s')}", []);

    return [$from, $to];
  }

  public function getFullDay(DateTime $anchor, int $addlDays = 0): array {
    $from = clone $anchor;
    $from->setTime(0, 0);

    $to = clone $from;
    $days = 1 + $addlDays;
    $to->modify("+{$days} day");

    return $this->convert($from, $to);
  }

  public function getWeekStartEnd(DateTime $anchor, int $addlWeeks = 0): array {
    $from = clone $anchor;
    $from->setTime(0, 0);

    $dayOfWeek = intval($anchor->format('w'));
    $from->modify("-" . $dayOfWeek . " days");

    $to = clone $from;
    $days = (1 + $addlWeeks) * 7;
    $to->modify("+{$days} day");

    return $this->convert($from, $to);
  }

  public function getFullMonth(DateTime $anchor, int $addlMonths = 0): array {
    $from = clone $anchor;
    $from->modify("first day of this month")->setTime(0, 0);

    $to = clone $from;
    $months = 1 + $addlMonths;
    $to->modify("+{$months} month");

    return $this->convert($from, $to);
  }

  public function getFullQuarter(DateTime $anchor, int $addlQuarters = 0): array {
    $quarter = ceil($anchor->format("n") / 3);

    $from = clone $anchor;
    $from->setDate($anchor->format("Y"), $quarter * 3 - 2, 1)->setTime(0, 0);

    $months = (1 + $addlQuarters) * 3;
    $to = (clone $from)->modify("+{$months} months");

    return $this->convert($from, $to);
  }

  public function getFullYear(DateTime $anchor, int $addlYears = 0): array {
    $from = clone $anchor;
    $from->modify("first day of this year")->setTime(0, 0);

    $to = clone $from;
    $years = 1 + $addlYears;
    $to->modify("+{$years} year");

    return $this->convert($from, $to);
  }
}
