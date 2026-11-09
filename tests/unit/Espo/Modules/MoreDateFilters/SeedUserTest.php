<?php

declare(strict_types=1);

namespace tests\unit\Espo\Modules\MoreDateFilters;

use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\RDBRepository;
use Espo\ORM\Repository\RDBSelectBuilder;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Fixtures\SeedUser;

require_once dirname(__DIR__, 4) . '/fixtures/SeedUser.php';

class SeedUserTest extends TestCase
{
    /** @dataProvider existingUsers */
    public function testSelectsExistingActiveUserWithoutCreatingAccounts(?int $match): void
    {
        $user = $this->createMock(User::class);
        $manager = $this->createMock(EntityManager::class);
        $repository = $this->createMock(RDBRepository::class);
        $manager->expects($this->once())->method('getRDBRepository')->with('User')->willReturn($repository);
        $manager->expects($this->never())->method('createEntity');
        $manager->expects($this->never())->method('saveEntity');
        $criteria = [
            ['isActive' => true, 'deleted' => false, 'type' => ['admin', 'super-admin'], 'userName' => 'admin'],
            ['isActive' => true, 'deleted' => false, 'type' => ['admin', 'super-admin']],
            ['isActive' => true, 'deleted' => false, 'type' => 'regular'],
        ];
        $index = 0;
        $repository->expects($this->exactly($match === null ? 3 : $match + 1))->method('where')
            ->willReturnCallback(function ($actual) use (&$index, $criteria, $match, $user) {
                $this->assertSame($criteria[$index], $actual);
                $query = $this->createMock(RDBSelectBuilder::class);
                $query->expects($this->once())->method('order')->with('id', 'ASC')->willReturnSelf();
                $query->expects($this->once())->method('findOne')->willReturn($index === $match ? $user : null);
                $index++;
                return $query;
            });
        if ($match === null) {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('No active admin or regular user exists');
        }
        $this->assertSame($user, SeedUser::find($manager));
    }

    public static function existingUsers(): array
    {
        return ['admin login' => [0], 'another admin' => [1], 'regular fallback' => [2], 'no eligible user' => [null]];
    }
}
