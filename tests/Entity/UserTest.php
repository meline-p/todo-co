<?php

namespace App\Tests\Entity;

use PHPUnit\Framework\TestCase;
use App\Entity\Task;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validation;

class UserTest extends TestCase
{
    // Username
    public function testSetUsername()
    {
        $task = new User();
        $task->setUsername("Test");
        $this->assertSame("Test", $task->getUsername());
    }

    // Roles
    public function testSetRoles()
    {
        $user = new User();

        $this->assertSame(['ROLE_USER'], $user->getRoles());

        $roles = ['ROLE_USER', 'ROLE_ADMIN'];
        $user->setRoles($roles);
        $this->assertSame($roles, $user->getRoles());

        $user->setRoles(['ROLE_USER']);
        $this->assertSame(['ROLE_USER'], $user->getRoles());
    }

    // Password
    public function testSetPassword()
    {
        $user = new User();

        $user->setPassword('mySecretPassword');
        $this->assertSame('mySecretPassword', $user->getPassword());
    }

    // Email
    public function testSetEmail()
    {
        $user = new User();

        $user->setEmail('test@test.com');
        $this->assertSame('test@test.com', $user->getEmail());
    }

    // addTask
    public function testAddTask()
    {
        $user = new User();
        $task = new Task();

        $user->addTask($task);
        $this->assertCount(1, $user->getTasks());

        $this->assertSame($user, $task->getAuthor());
    }

    // removeTask
    public function testRemoveTask()
    {
        $user = new User();
        $task = new Task();

        $user->addTask($task);
        $this->assertCount(1, $user->getTasks());

        $user->removeTask($task);
        $this->assertCount(0, $user->getTasks());

        $this->assertNull($task->getAuthor());
    }


}
