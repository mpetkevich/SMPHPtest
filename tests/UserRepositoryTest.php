<?php

use App\User;
use App\UserRepository;
use App\MysqliDatabaseConnection;
use PHPUnit\Framework\TestCase;

class UserRepositoryTest extends TestCase
{
    public function testSave()
    {
        $db = $this->createMock(MysqliDatabaseConnection::class);
        $user = new User('2cdaab9c-0f32-b9c0-aec0-4cc27e407cff', 'John Doe', 'johndoe@example.com');
        $userRepository = new UserRepository($db);
        $userRepository->save($user);

        $this->assertEquals($user, $userRepository->find(1));
    }

    public function testFind()
    {
        $db = $this->createMock(MysqliDatabaseConnection::class);
        $userRepository = new UserRepository($db);

        $this->assertEquals(null, $userRepository->find(1));

        $user = new User(1, 'John Doe', 'johndoe@example.com');
        $userRepository->save($user);

        $this->assertEquals($user, $userRepository->find(1));
    }
}
