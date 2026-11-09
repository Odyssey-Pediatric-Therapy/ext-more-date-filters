<?php

declare(strict_types=1);

// Manual test fixture seeder. Not part of the extension package or normal test runs.
use Tests\Fixtures\DateFilterSeed;

require dirname(__DIR__) . '/src/files/custom/Espo/Modules/MoreDateFilters/Services/Core.php';

try {
    $options = [];
    $valueOptions = ['count', 'timezone', 'week-start', 'run-id', 'all-day-percent', 'start-time', 'end-time'];
    for ($i = 1; $i < count($argv); $i++) {
        $parts = explode('=', $argv[$i], 2);
        $key = substr($parts[0], 2);
        if (!str_starts_with($parts[0], '--') || !in_array($key, [...$valueOptions, 'dry-run', 'help'], true)) {
            throw new InvalidArgumentException('Unknown argument: ' . $argv[$i]);
        }
        if (in_array($key, $valueOptions, true)) {
            $value = $parts[1] ?? ($argv[++$i] ?? null);
            if ($value === null || $value === '' || str_starts_with($value, '--')) {
                throw new InvalidArgumentException("--$key requires a value.");
            }
            $options[$key] = $value;
        } else {
            if (count($parts) > 1) throw new InvalidArgumentException("--$key does not take a value.");
            $options[$key] = true;
        }
    }
    if (isset($options['help'])) {
        echo <<<'HELP'
Create manual test data for More Date Filters (test/development Espo instance only).

npm run seed -- --dry-run
npm run seed

Options:
  --count=2000          Minimum spread records PER entity; totals randomize above this minimum.
  --start-time=0800     Earliest start for ordinary timed events (HHMM or HH:MM).
  --end-time=1800       Latest end for ordinary timed events, on the same local day.
  --timezone=ZONE       Defaults to the test instance timezone, then UTC.
  --week-start=0        Defaults to the test instance setting; 0=Sunday, 1=Monday, ... 6=Saturday.
  --run-id=LABEL        Identifier included in each record description.
  --all-day-percent=3   All-day share for Tasks/Meetings, from 0 to 10 percent.
  --dry-run            Summarize generated fixtures without booting Espo or connecting a database.
  --help               Show this help.

The target is always this repository's site installation.
Every run appends a new batch. Inserts run in one transaction with hooks disabled.
Dates span execution time minus two calendar years through plus two calendar years.
HELP;
        echo "\n";
        exit(0);
    }

    require __DIR__ . '/fixtures/DateFilterSeed.php';
    $site = dirname(__DIR__) . '/site';
    $site = realpath($site) ?: $site;
    $settings = is_file($site . '/data/config.php') ? require $site . '/data/config.php' : [];
    $settings = is_array($settings) ? $settings : [];
    if (!preg_match('/^[0-9]+$/D', $options['count'] ?? '2000')) {
        throw new InvalidArgumentException('--count must be a whole number.');
    }
    if (!preg_match('/^[0-6]$/D', (string) ($options['week-start'] ?? $settings['weekStart'] ?? 0))) {
        throw new InvalidArgumentException('--week-start must be between 0 and 6.');
    }
    if (!preg_match('/^(?:[0-9]|10)$/D', $options['all-day-percent'] ?? '3')) {
        throw new InvalidArgumentException('--all-day-percent must be between 0 and 10.');
    }
    $timezone = new DateTimeZone($options['timezone'] ?? $settings['timeZone'] ?? 'UTC');
    $now = new DateTimeImmutable('now', $timezone);
    $runId = $options['run-id'] ?? bin2hex(random_bytes(3));
    $plan = new DateFilterSeed($now, $runId, (int) ($options['count'] ?? 2000), (int) ($options['week-start'] ?? $settings['weekStart'] ?? 0), (int) ($options['all-day-percent'] ?? 3),
        startTime: $options['start-time'] ?? '0800', endTime: $options['end-time'] ?? '1800');

    printf("Run: %s\nTimezone: %s\nRange: %s through %s\n", $runId, $timezone->getName(), $plan->from->format('c'), $plan->to->format('c'));
    printf("Ordinary timed events: %s–%s local time; explicit boundary fixtures are exceptions.\n", $plan->startTime, $plan->endTime);
    $counts = ['Task' => 0, 'Meeting' => 0, 'Call' => 0];
    $allDay = ['Task' => 0, 'Meeting' => 0];
    $undated = 0;
    $days = [];
    $calendarFixtures = ['DST transitions' => ['Task' => 0, 'Meeting' => 0, 'Call' => 0], 'Leap days' => ['Task' => 0, 'Meeting' => 0, 'Call' => 0]];
    $summarize = static function (array $record) use (&$counts, &$allDay, &$undated, &$calendarFixtures, &$days, $timezone): void {
        $counts[$record['entityType']]++;
        $date = $record['attributes'][$record['entityType'] === 'Task' ? 'dateEnd' : 'dateStart'];
        if ($date !== null) {
            $day = (new DateTimeImmutable($date, new DateTimeZone('UTC')))->setTimezone($timezone)->format('Y-m-d');
            $type = $record['entityType'];
            $days[$type][$day] = ($days[$type][$day] ?? 0) + 1;
        }
        foreach (['DST transitions' => 'dst:', 'Leap days' => 'leap-day:'] as $label => $marker) {
            if (str_contains($record['attributes']['description'], $marker)) $calendarFixtures[$label][$record['entityType']]++;
        }
        if (!empty($record['attributes']['dateStartDate'])) $allDay[$record['entityType']]++;
        if ($record['attributes']['dateStart'] === null) $undated++;
    };

    if (isset($options['dry-run'])) {
        foreach ($plan->records() as $record) $summarize($record);
        echo "Dry run: no database connection or writes.\n";
    } else {
        if (!is_file($site . '/bootstrap.php')) throw new RuntimeException('Test Espo instance not found: ' . $site);
        require $site . '/bootstrap.php';
        $app = new Espo\Core\Application();
        $app->setupSystemUser();
        $manager = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class);
        require __DIR__ . '/fixtures/SeedUser.php';
        require __DIR__ . '/fixtures/SeedWriter.php';
        $user = \Tests\Fixtures\SeedUser::find($manager);
        $userId = $user->getId();
        printf("Assigning fixtures to existing user: %s\n", $user->get('userName'));
        $manager->getTransactionManager()->run(static function () use ($manager, $plan, $userId, $summarize): void {
            foreach ($plan->records() as $record) {
                \Tests\Fixtures\SeedWriter::save($manager, $record, $userId);
                $summarize($record);
            }
        });
        echo "Committed test fixtures.\n";
    }
    foreach ($counts as $entityType => $count) printf("%s: %d\n", $entityType, $count);
    foreach ($days as $type => $dailyCounts) printf("%s distribution: %d days, %d today, peak %d/day\n", $type, count($dailyCounts), $dailyCounts[$now->format('Y-m-d')] ?? 0, max($dailyCounts));
    foreach ($allDay as $entityType => $count) printf("All-day %s: %d (%.1f%%)\n", $entityType, $count, $count / $counts[$entityType] * 100);
    printf("Undated tasks: %d\n", $undated);
    foreach ($calendarFixtures as $label => $byType) {
        printf("%s fixtures: Task=%d, Meeting=%d, Call=%d\n", $label, $byType['Task'], $byType['Meeting'], $byType['Call']);
    }
    printf("Find this batch by description text: Run: %s.\n", $runId);
} catch (Throwable $e) {
    fwrite(STDERR, 'Test seeder failed: ' . $e->getMessage() . "\n");
    exit(1);
}
