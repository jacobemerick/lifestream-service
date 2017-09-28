<?php

namespace Jacobemerick\LifestreamService\Model;

use Aura\Sql\ExtendedPdo;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{

    public function testIsInstanceOfUser()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $model = new User($mockPdo);

        $this->assertInstanceOf(User::class, $model);
    }

    public function testConstructSetsExtendedPdo()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $model = new User($mockPdo);

        $this->assertAttributeSame($mockPdo, 'extendedPdo', $model);
    }

    public function testGetUserIdSendsParams()
    {
        $user = 'some user';

        $query = "
            SELECT `id`
            FROM `user`
            WHERE `name` = :user
            LIMIT 1";
        $bindings = [
            'user' => $user,
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchValue')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new User($mockPdo);
        $model->getUserId($user);
    }

    public function testGetUserIdReturnsId()
    {
        $userId = 1;

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchValue')
            ->willReturn($userId);

        $model = new User($mockPdo);
        $result = $model->getUserId('');

        $this->assertSame($userId, $result);
    }
}
