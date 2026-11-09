<?php

declare(strict_types=1);

namespace tests\unit\Espo\Modules\MoreDateFilters;

use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\RDBRelation;
use Espo\ORM\Repository\RDBRepository;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\SeedWriter;

require_once dirname(__DIR__, 4) . '/fixtures/SeedWriter.php';

class SeedWriterTest extends TestCase
{
    /** @dataProvider entityTypes */
    public function testOwnerAndCalendarAttendeeAreSaved(string $type): void
    {
        $manager = $this->createMock(EntityManager::class);
        $entity = $this->createMock(Entity::class);
        $record = ['entityType' => $type, 'attributes' => ['name' => 'Budget review', 'dateStart' => '2025-09-09 12:00:00']];
        $manager->expects($this->once())->method('createEntity')->with($type, [
            'name' => 'Budget review', 'dateStart' => '2025-09-09 12:00:00',
            'assignedUserId' => 'admin-id', 'createdById' => 'admin-id', 'modifiedById' => 'admin-id',
        ], ['skipAll' => true])->willReturn($entity);
        if ($type === 'Task') {
            $manager->expects($this->never())->method('getRDBRepository');
        } else {
            $repository = $this->createMock(RDBRepository::class);
            $relation = $this->createMock(RDBRelation::class);
            $manager->expects($this->once())->method('getRDBRepository')->with($type)->willReturn($repository);
            $repository->expects($this->once())->method('getRelation')->with($entity, 'users')->willReturn($relation);
            $relation->expects($this->once())->method('relateById')
                ->with('admin-id', ['status' => 'Accepted'], ['skipHooks' => true]);
        }
        $this->assertSame($entity, SeedWriter::save($manager, $record, 'admin-id'));
    }

    public static function entityTypes(): array
    {
        return [['Task'], ['Meeting'], ['Call']];
    }
}
