<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use InvalidArgumentException;

/** Persist test fixtures, including the attendee links required by Espo's calendar. */
final class SeedWriter
{
    /** @param array{entityType: string, attributes: array<string, mixed>} $record */
    public static function save(EntityManager $manager, array $record, string $userId): Entity
    {
        $type = $record['entityType'];
        if (!in_array($type, ['Task', 'Meeting', 'Call'], true)) {
            throw new InvalidArgumentException('Unsupported fixture entity type.');
        }
        $attributes = $record['attributes'];
        $attributes['assignedUserId'] = $userId;
        $attributes['createdById'] = $attributes['modifiedById'] = $userId;
        $entity = $manager->createEntity($type, $attributes, ['skipAll' => true]);
        if ($type !== 'Task') {
            $manager->getRDBRepository($type)->getRelation($entity, 'users')
                ->relateById($userId, ['status' => 'Accepted'], ['skipHooks' => true]);
        }
        return $entity;
    }
}
