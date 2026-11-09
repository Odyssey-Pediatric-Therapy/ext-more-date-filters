<?php

declare(strict_types=1);

namespace Tests\Tools;

use Espo\ORM\EntityManager;
use Espo\ORM\Query\DeleteBuilder;
use Espo\ORM\Query\SelectBuilder;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Empty fixture entity tables and their attendee links, including non-seeded rows.
 * @return array<string, int> Affected rows per entity.
 */
function emptyDateFilterTables(EntityManager $manager, bool $dryRun = false): array
{
    $execute = static function () use ($manager, $dryRun): array {
        $counts = [];
        $executor = $manager->getQueryExecutor();
        foreach (['MeetingUser', 'CallUser', 'Task', 'Meeting', 'Call'] as $entityType) {
            if ($dryRun) {
                $query = SelectBuilder::create()->from($entityType)->select('COUNT:(id)', 'count')->build();
                $counts[$entityType] = (int) $executor->execute($query)->fetchColumn();
            } else {
                // Hard delete all rows, including soft-deleted records. No hooks or notifications.
                $query = DeleteBuilder::create()->from($entityType)->build();
                $counts[$entityType] = $executor->execute($query)->rowCount();
            }
        }
        return $counts;
    };

    return $dryRun ? $execute() : $manager->getTransactionManager()->run($execute);
}

// Requiring this file from a test only defines the function; it never boots Espo or deletes data.
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== __FILE__) return;

try {
    $site = dirname(__DIR__) . '/site';
    $dryRun = false;
    $help = false;
    for ($i = 1; $i < count($argv); $i++) {
        if ($argv[$i] === '--dry-run') {
            $dryRun = true;
        } elseif ($argv[$i] === '--help') {
            $help = true;
        } else {
            throw new InvalidArgumentException('Unknown argument: ' . $argv[$i]);
        }
    }
    if ($help) {
        echo <<<'HELP'
Empty the Task, Meeting and Call tables in a test/development Espo instance.
Permanently deletes ALL rows in these three tables, including non-seeded and soft-deleted rows.
Also empties MeetingUser and CallUser attendee links. User accounts are preserved.
Deletes run in one transaction without hooks.

npm run empty -- --dry-run
npm run empty

The target is always this repository's site installation (same as the seeder).
--dry-run    Count rows only; connects to the database but does not delete anything.
--help       Show this help without connecting to the database.
HELP;
        echo "\n";
        exit(0);
    }
    $site = realpath($site) ?: $site;
    if (!is_file($site . '/bootstrap.php')) throw new RuntimeException('Test Espo instance not found: ' . $site);
    printf("Test instance: %s\n", $site);
    require $site . '/bootstrap.php';
    $app = new \Espo\Core\Application();
    $app->setupSystemUser();
    $manager = $app->getContainer()->getByClass(EntityManager::class);
    $counts = emptyDateFilterTables($manager, $dryRun);
    foreach ($counts as $entityType => $count) printf("%s: %d %s\n", $entityType, $count, $dryRun ? 'rows would be deleted' : 'rows deleted');
    echo $dryRun ? "Dry run: no rows deleted.\n" : "Committed: Task, Meeting and Call tables emptied.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Test table cleanup failed: ' . $e->getMessage() . "\n");
    exit(1);
}
