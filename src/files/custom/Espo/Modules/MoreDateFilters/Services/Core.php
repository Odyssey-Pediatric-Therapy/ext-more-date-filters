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

  public function getWeekStartEnd(DateTime $anchor, int $addlWeeks = 0, bool $returnUtc = false): array {
    $this->log->debug("Start    ",[$anchor]);
    $tz = $anchor->getTimezone();
    $this->log->debug("Tz       ",[$tz->getName()]);

    $anchor->setTime(0,0);
    $this->log->debug("After 0,0",[$anchor]);

    if($tz->getName() !== "UTC") {
      $this->log->debug("!= UTC",[]);
      $utc = new DateTimeZone("UTC");
      $anchor->setTimezone($utc);
    }
    $this->log->debug("After UTC",[$anchor]);

    $dayOfWeek = intval($anchor->format('w'));

    $from = (clone $anchor)->modify("-" . $dayOfWeek . " days");
    $to = (clone $from)->add(new DateInterval("P7D"));
    #$to = (clone $anchor)->modify("+" . (7 - $dayOfWeek) . " days")->modify("+{$addlWeeks} weeks");

    $this->log->debug("From To  ",[$from, $to]);

    if(!$returnUtc && $tz->getName() !== "UTC") {
      $from->setTimezone($tz);
      $to->setTimezone($tz);
    }
    $this->log->debug("From To  ",[$from, $to]);

    return array($from, $to);
  }

  public function getFullDay(DateTime $anchor, int $addlDays = 0, bool $returnUtc = false): array {
    $from = (clone $anchor)->setTime(0, 0);

    if($returnUtc) {
      $utc = new DateTimeZone("UTC");
      $from->setTimezone($utc);
    }

    $to = (clone $from)->modify("+" . (($addlDays + 1)) . " days");

    return array($from, $to);
  }

  public function getFullMonth(DateTime $anchor, int $addlMonths = 0, bool $returnUtc = false): array {
    $utc = new DateTimeZone("UTC");

    $timezone = $anchor->getTimezone();
    $from = (clone $anchor)->setTimezone($utc)->modify("first day of this month")->setTime(0, 0);
    $to = (clone $from)->modify("first day of +" . ($addlMonths + 1) . " month");

    if(!$returnUtc) {
      $from->setTimezone($timezone);
      $to->setTimezone($timezone);
    }

    return [$from, $to];
  }

  public function getFullQuarter(DateTime $anchor, int $addlQuarters = 0, bool $returnUtc = false): array {
    $month = intval($anchor->format('n'));
    $quarterStartMonth = (int)floor(($month - 1) / 3) * 3 + 1;
    $from = (clone $anchor)->modify("first day of {$quarterStartMonth} month");

    if($returnUtc) {
      $utc = new DateTimeZone("UTC");
      $from->setTimezone($utc);
    }

    $to = (clone $from)->modify("+" . (3 * ($addlQuarters + 1)) . " months");

    return [$from, $to];
  }

  public function getFullYear(DateTime $anchor, int $addlYears = 0, bool $returnUtc = false): array {
    $from = (clone $anchor)->modify("first day of January")->setTime(0, 0);
    $to = (clone $from)->modify("first day of January +" . ($addlYears + 1) . " year");

    if($returnUtc) {
      $utc = new DateTimeZone("UTC");
      $from->setTimezone($utc);
      $to->setTimezone($utc);
    }

    return [$from, $to];
  }
}
