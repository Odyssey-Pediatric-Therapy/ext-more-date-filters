<?php

declare(strict_types=1);

namespace tests\unit\Espo\Modules\MoreDateFilters;

use Espo\ORM\EntityManager;
use Espo\ORM\Executor\QueryExecutor;
use Espo\ORM\Query\Delete;
use Espo\ORM\Query\Select;
use Espo\ORM\TransactionManager;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use function Tests\Tools\emptyDateFilterTables;

require_once dirname(__DIR__, 4) . '/empty-date-filter-tables.php';

class EmptyTablesTest extends TestCase
{
    public function testFixtureTablesAndAttendeeLinksAreEmptiedInOneTransaction(): void
    {
        $manager = $this->createMock(EntityManager::class);
        $executor = $this->createMock(QueryExecutor::class);
        $transaction = $this->createMock(TransactionManager::class);
        $manager->method('getQueryExecutor')->willReturn($executor);
        $manager->expects($this->once())->method('getTransactionManager')->willReturn($transaction);
        $transaction->expects($this->once())->method('run')->willReturnCallback(fn ($execute) => $execute());
        $tables = [];
        $executor->expects($this->exactly(5))->method('execute')->willReturnCallback(function ($query) use (&$tables) {
            $this->assertInstanceOf(Delete::class, $query);
            $this->assertEmpty($query->getWhere(), 'The request empties the tables, not just one seeded batch.');
            $tables[] = $query->getFrom();
            $statement = $this->createMock(PDOStatement::class);
            $statement->method('rowCount')->willReturn(2000);
            return $statement;
        });
        $this->assertSame(['MeetingUser' => 2000, 'CallUser' => 2000, 'Task' => 2000, 'Meeting' => 2000, 'Call' => 2000], emptyDateFilterTables($manager));
        $this->assertSame(['MeetingUser', 'CallUser', 'Task', 'Meeting', 'Call'], $tables);
    }

    public function testDryRunOnlyCountsRows(): void
    {
        $manager = $this->createMock(EntityManager::class);
        $executor = $this->createMock(QueryExecutor::class);
        $manager->method('getQueryExecutor')->willReturn($executor);
        $manager->expects($this->never())->method('getTransactionManager');
        $tables = [];
        $executor->expects($this->exactly(5))->method('execute')->willReturnCallback(function ($query) use (&$tables) {
            $this->assertInstanceOf(Select::class, $query);
            $this->assertEmpty($query->getWhere());
            $tables[] = $query->getFrom();
            $statement = $this->createMock(PDOStatement::class);
            $statement->method('fetchColumn')->willReturn('2500');
            return $statement;
        });
        $this->assertSame(['MeetingUser' => 2500, 'CallUser' => 2500, 'Task' => 2500, 'Meeting' => 2500, 'Call' => 2500], emptyDateFilterTables($manager, true));
        $this->assertSame(['MeetingUser', 'CallUser', 'Task', 'Meeting', 'Call'], $tables);
    }
}
