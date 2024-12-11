<?php

namespace App\Tests\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class TaskControllerTest extends WebTestCase
{
    // /tasks
    private function makeRequest(string $filter): Response
    {
        $mockTaskRepository = $this->createMock(TaskRepository::class);
        $method = ($filter === 'all') ? 'findAll' : 'findBy';
        $criteria = ($filter === 'all') ? [] : ['isDone' => ($filter === 'is_done')];

        $mockTaskRepository->method($method)
            ->with($criteria)
            ->willReturn([new Task(), new Task()]);

        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneByEmail('toto@toto.fr');

        $client->loginUser($testUser);

        $client->request('GET', '/tasks?status=' . $filter);

        return $client->getResponse();
    }

    // public function testGetTasksWithoutLogin()
    // {
    //     $client = static::createClient();
    //     $client->request('GET', '/tasks');

    //     $this->assertResponseRedirects('/login');
    // }

    public function testListWithoutFilter()
    {
        $response = $this->makeRequest('all');

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('tasks', $response->getContent());
    }

    public function testListWithIsDoneFilter()
    {
        $response = $this->makeRequest('is_done');

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('tasks', $response->getContent());
    }

    public function testListWithInProgressFilter()
    {
        $response = $this->makeRequest('in_progress');

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('tasks', $response->getContent());
    }

    // /tasks/create
    public function testCreateTaskWithoutLogin()
    {
        $client = static::createClient();
        $client->request('GET', '/tasks/create');

        $this->assertResponseRedirects('/login');
    }

    public function testCreateTaskFormIsAccessible()
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneByEmail('toto@toto.fr');

        $taskRepository = static::getContainer()->get(TaskRepository::class);

        $client->loginUser($user);
        $client->request('GET', '/tasks/create');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="task[title]"]');
        $this->assertSelectorExists('textarea[name="task[content]"]');
    }

    public function testCreateTaskWithValidData()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneByEmail('toto@toto.fr');

        $client->loginUser($user);

        $client->request('GET', '/tasks/create');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');

        $client->submitForm('Ajouter', [
            'task[title]' => 'Test Task',
            'task[content]' => 'A description of the task.',
        ]);

        $this->assertResponseRedirects('/tasks');

        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $task = $taskRepository->findOneBy(['title' => 'Test Task']);

        $this->assertNotNull($task);
        $this->assertEquals('Test Task', $task->getTitle());
        $this->assertEquals('A description of the task.', $task->getContent());
        $this->assertFalse($task->isDone());
    }

    public function testCreateTaskWithInvalidData()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneByEmail('toto@toto.fr');

        $client->loginUser($user);

        $client->request('GET', '/tasks/create');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');

        $client->submitForm('Ajouter', [
            'task[title]' => '',
            'task[content]' => 'A description of the task.',
        ]);

        $this->assertSelectorTextContains('li', 'Le titre est obligatoire.');
    }

    // /tasks/edit
    public function testEditTaskFormIsAccessible()
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneByEmail('toto@toto.fr');

        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $task = $taskRepository->findOneBy(['title' => 'Test Task']);
        $this->assertNotNull($task, 'La tâche avec le titre "Test Task" n\'existe pas.');

        $client->loginUser($user);
        $client->request('GET', '/tasks/' . $task->getId() . '/edit');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="task[title]"]');
        $this->assertSelectorExists('textarea[name="task[content]"]');
    }

    public function testEditTaskWithValidData()
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneByEmail('toto@toto.fr');

        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $task = $taskRepository->findOneBy(['title' => 'Test Task']);
        $this->assertNotNull($task, 'La tâche avec le titre "Test Task" n\'existe pas.');

        $client->loginUser($user);
        $client->request('GET', '/tasks/' . $task->getId() . '/edit');

        $client->submitForm('Modifier', [
            'task[title]' => 'Updated Task Title',
            'task[content]' => 'Updated task description.',
        ]);

        $updatedTask = $taskRepository->find($task->getId());

        $this->assertEquals('Updated Task Title', $updatedTask->getTitle());
        $this->assertEquals('Updated task description.', $updatedTask->getContent());

        $this->assertResponseRedirects('/tasks');

        $client->followRedirect();
        $this->assertSelectorTextContains('.bg-light-green', 'La tâche Updated Task Title a bien été modifiée.');
    }

    public function testEditTaskWithInvalidData()
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneByEmail('toto@toto.fr');

        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $task = $taskRepository->findOneBy(['title' => 'Updated Task Title']);
        $this->assertNotNull($task, 'La tâche avec le titre "Updated Task Title" n\'existe pas.');

        $client->loginUser($user);
        $client->request('GET', '/tasks/' . $task->getId() . '/edit');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');

        $client->submitForm('Modifier', [
            'task[title]' => '',
            'task[content]' => 'Updated task description.',
        ]);

        $this->assertSelectorTextContains('li', 'Le titre est obligatoire.');

        $unchangedTask = $taskRepository->find($task->getId());
        $this->assertNotEquals('', $unchangedTask->getTitle());
    }

    // tasks/toggle
    public function testToggleTask()
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneByEmail('toto@toto.fr');

        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $new_task = new Task();
        $new_task->setTitle('Task 1');
        $new_task->setContent('Task content');
        $new_task->setAuthor($user);
        $new_task->setIsDone(true);
        $em->persist($new_task);
        $em->flush();

        $new_task = new Task();
        $new_task->setTitle('Task 2');
        $new_task->setContent('Task content');
        $new_task->setAuthor($user);
        $new_task->setIsDone(false);
        $em->persist($new_task);
        $em->flush();

        // task->isDone(true)
        $task = $taskRepository->findOneBy(['author' => $user, 'isDone' => true]);
        $this->assertNotNull($task, 'La tâche avec le titre "Task 1" n\'existe pas.');

        $client->loginUser($user);

        $initialStatus = $task->setIsDone(true);
        $client->request('GET', '/tasks/' . $task->getId() . '/toggle');

        $updatedTask = $taskRepository->find($task->getId());
        $this->assertNotEquals($initialStatus, $updatedTask->isDone());

        $this->assertResponseRedirects('/tasks');

        $client->followRedirect();

        // task->isDone(false)
        $task = $taskRepository->findOneBy(['author' => $user, 'isDone' => false ]);
        $this->assertNotNull($task, 'La tâche avec le titre "Task 2" n\'existe pas.');

        $initialStatus = $task->setIsDone(true);
        $client->request('GET', '/tasks/' . $task->getId() . '/toggle');

        $updatedTask = $taskRepository->find($task->getId());
        $this->assertNotEquals($initialStatus, $updatedTask->isDone());

        $this->assertResponseRedirects('/tasks');

        $client->followRedirect();
    }

    // tasks/delete
    public function testUserCanDeleteTask(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneByEmail('toto@toto.fr');

        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $task = $taskRepository->findOneBy(['author' => $user]);

        $this->assertNotNull($task, 'La tâche à supprimer n\'a pas été trouvée.');

        $client->loginUser($user);
        $client->request('GET', '/tasks/' . $task->getId() . '/delete');

        $this->assertEquals($task->getId(), null);

        $this->assertResponseRedirects('/tasks');

        $client->followRedirect();
        $this->assertSelectorTextContains('.bg-light-green', 'La tâche a bien été supprimée.');
    }

    public function testAdminCanDeleteAnonymousTask(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $admin = $userRepository->findOneByEmail('toto@toto.fr');
        $this->assertNotNull($admin, 'L\'utilisateur admin n\'a pas été trouvé dans la base de données.');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $new_task = new Task();
        $new_task->setTitle('Anonymous Task');
        $new_task->setContent('Task content');
        $new_task->setAuthor(null);
        $em->persist($new_task);
        $em->flush();

        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $task = $taskRepository->findOneBy(['author' => null]);

        $this->assertNotNull($task, 'La tâche à supprimer n\'a pas été trouvée.');

        $client->loginUser($admin);
        $client->request('GET', '/tasks/' . $task->getId() . '/delete');

        $this->assertEquals($task->getId(), null);

        $this->assertResponseRedirects('/tasks');

        $client->followRedirect();
        $this->assertSelectorTextContains('.bg-light-green', 'La tâche a bien été supprimée.');
    }

    public function testUserCannotDeleteTask(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $user1 = $userRepository->findOneByEmail('toto@toto.fr');
        $user2 = $userRepository->findOneByEmail('john@doe.com');

        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $task = $taskRepository->findOneBy(['author' => $user1]);

        $this->assertNotNull($task, 'La tâche à supprimer n\'a pas été trouvée.');

        $client->loginUser($user2);
        $client->request('GET', '/tasks/' . $task->getId() . '/delete');

        $this->assertResponseStatusCodeSame(403);
    }
}
