<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use RuntimeException;

/** Select an existing local test user without requiring an ID or creating accounts. */
final class SeedUser
{
    public static function find(EntityManager $manager): Entity
    {
        $repository = $manager->getRDBRepository('User');
        $active = ['isActive' => true, 'deleted' => false];
        foreach ([
            ['type' => ['admin', 'super-admin'], 'userName' => 'admin'],
            ['type' => ['admin', 'super-admin']],
            ['type' => 'regular'],
        ] as $criteria) {
            $user = $repository->where($active + $criteria)->order('id', 'ASC')->findOne();
            if ($user !== null) return $user;
        }
        throw new RuntimeException('No active admin or regular user exists in the local test site.');
    }
}
