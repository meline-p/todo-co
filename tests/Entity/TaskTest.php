<?php

namespace App\Tests\Entity;

use PHPUnit\Framework\TestCase;
use App\Entity\Task;
use App\Entity\User;
use DateTime;
use DateTimeImmutable;
use Symfony\Component\Validator\Validation;

class TaskTest extends TestCase
{
    // Title
    public function testSetTitle()
    {
        $task = new Task();
        $task->setTitle("Test");
        $this->assertSame("Test", $task->getTitle());
    }

    // Content
    public function testSetContent()
    {
        $task = new Task();
        $task->setContent("Test Contenu");
        $this->assertSame("Test Contenu", $task->getContent());
    }

    // IsDone
    public function testSetIsDone()
    {
        $task = new Task();

        $task->setIsDone(true);
        $this->assertTrue($task->isDone());

        $task->setIsDone(false);
        $this->assertFalse($task->isDone());

        $task->setIsDone(null);
        $this->assertNull($task->isDone());
    }

    // Toggle
    public function testToggleInverse()
    {
        $task = new Task();

        $task->toggle(false);
        $this->assertFalse($task->isDone());

        $task->toggle(!$task->isDone());
        $this->assertTrue($task->isDone());

        $task->toggle(!$task->isDone());
        $this->assertFalse($task->isDone());
    }

    // Author
    public function testSetGetAuthor()
    {
        $task = new Task();

        $user = new User();
        $user->setUsername("JohnDoe");

        $task->setAuthor($user);
        $this->assertSame($user, $task->getAuthor());

        $task->setAuthor(null);
        $this->assertNull($task->getAuthor());
    }

    // Created At
    public function testSetCreatedAt()
    {
        $task = new Task();
        $dateTime = new DateTimeImmutable();
        $task->setCreatedAt($dateTime);
        $this->assertSame($dateTime, $task->getCreatedAt());
    }

}
