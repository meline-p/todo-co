<?php

namespace App\Tests\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use App\DataFixtures\TasksFixtures;
use App\DataFixtures\UsersFixtures;
use App\Entity\User;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class TaskControllerTest extends WebTestCase
{
    protected ?AbstractDatabaseTool $databaseTool = null;
    private KernelBrowser|null $client = null;
    private UserRepository|null $userRepository = null;
    private User|null $admin = null;
    private User|null $user = null;

    public function setUp(): void
    {
        $this->client = static::createClient();

        $this->userRepository = static::getContainer()->get(UserRepository::class);

        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        $this->databaseTool->loadFixtures([
            UsersFixtures::class,
            TasksFixtures::class,
        ]);

        $this->admin = $this->userRepository->findOneByEmail('user@admin.com');
        $this->user = $this->userRepository->findOneByEmail('user@user.com');

        $this->assertNotNull($this->admin, 'Aucun utilisateur avec le rôle ROLE_ADMIN n\'a été trouvé dans la base de données.');
        $this->assertNotNull($this->user, 'Aucun utilisateur avec le rôle ROLE_USER n\'a été trouvé dans la base de données.');
    }

    // /tasks
    private function makeRequest(string $filter): Response
    {
        $mockTaskRepository = $this->createMock(TaskRepository::class);
        $method = ($filter === 'all') ? 'findAll' : 'findBy';
        $criteria = ($filter === 'all') ? [] : ['isDone' => ($filter === 'is_done')];

        $mockTaskRepository->method($method)
            ->with($criteria)
            ->willReturn([new Task(), new Task()]);

        $this->client->loginUser($this->user);

        $this->client->request('GET', '/tasks?status=' . $filter);

        return $this->client->getResponse();
    }

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
        $this->client->request('GET', '/tasks/create');

        $this->assertResponseRedirects('/login');
    }

    public function testCreateTaskFormIsAccessible()
    {
        $this->client->loginUser($this->user);
        $this->client->request('GET', '/tasks/create');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="task[title]"]');
        $this->assertSelectorExists('textarea[name="task[content]"]');
        $this->assertSelectorExists('input[name="task[dueDate]"]');
        $this->assertSelectorExists('select[name="task[priority]"]');
    }

    public function testCreateTaskWithValidData()
    {
        $this->client->loginUser($this->user);

        $this->client->request('GET', '/tasks/create');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');

        $this->client->submitForm('Ajouter', [
            'task[title]' => 'Test Task',
            'task[content]' => 'A description of the task.',
            'task[dueDate]' => '2024-12-31',
            'task[priority]' => 3,
        ]);

        $this->assertResponseRedirects('/tasks');

        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $task = $taskRepository->findOneBy(['title' => 'Test Task']);

        $this->assertNotNull($task);
        $this->assertEquals('Test Task', $task->getTitle());
        $this->assertEquals('A description of the task.', $task->getContent());
        $this->assertFalse($task->isDone());
        $this->assertEquals(new \DateTime('2024-12-31'), $task->getDueDate());
        $this->assertSame('high', $task->getPriority());
    }

    public function testCreateTaskWithInvalidData()
    {
        $this->client->loginUser($this->user);

        $this->client->request('GET', '/tasks/create');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');

        $this->client->submitForm('Ajouter', [
            'task[title]' => '',
            'task[content]' => 'A description of the task.',
        ]);

        $this->assertSelectorTextContains('li', 'Le titre est obligatoire.');
    }

    // /tasks/edit
    public function testEditTaskFormIsAccessible()
    {
        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $task = $taskRepository->findOneBy(['title' => 'Réserver une salle de réunion']);
        $this->assertNotNull($task, 'La tâche avec le titre "Réserver une salle de réunion" n\'existe pas.');

        $this->client->loginUser($this->user);
        $this->client->request('GET', '/tasks/' . $task->getId() . '/edit');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="task[title]"]');
        $this->assertSelectorExists('textarea[name="task[content]"]');
        $this->assertSelectorExists('input[name="task[dueDate]"]');
        $this->assertSelectorExists('select[name="task[priority]"]');
    }

    public function testEditTaskWithValidData()
    {
        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $task = $taskRepository->findOneBy(['title' => 'Réserver une salle de réunion']);
        $this->assertNotNull($task, 'La tâche avec le titre "Réserver une salle de réunion" n\'existe pas.');

        $this->client->loginUser($this->user);
        $this->client->request('GET', '/tasks/' . $task->getId() . '/edit');

        $this->client->submitForm('Modifier', [
            'task[title]' => 'Updated Task Title',
            'task[content]' => 'Updated task description.',
            'task[dueDate]' => '2025-03-28',
            'task[priority]' => 1,
        ]);

        $updatedTask = $taskRepository->find($task->getId());

        $this->assertEquals('Updated Task Title', $updatedTask->getTitle());
        $this->assertEquals('Updated task description.', $updatedTask->getContent());

        $this->assertResponseRedirects('/tasks');

        $this->client->followRedirect();
        $this->assertSelectorTextContains('.bg-light-green', 'La tâche Updated Task Title a bien été modifiée.');
    }

    public function testEditTaskWithInvalidData()
    {
        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $task = $taskRepository->findOneBy(['title' => 'Réserver une salle de réunion']);
        $this->assertNotNull($task, 'La tâche avec le titre "Réserver une salle de réunion" n\'existe pas.');

        $this->client->loginUser($this->user);
        $this->client->request('GET', '/tasks/' . $task->getId() . '/edit');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');

        $this->client->submitForm('Modifier', [
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
        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $new_task = new Task();
        $new_task->setTitle('Task 1');
        $new_task->setContent('Task content');
        $new_task->setAuthor($this->user);
        $new_task->setIsDone(true);
        $em->persist($new_task);
        $em->flush();

        $new_task = new Task();
        $new_task->setTitle('Task 2');
        $new_task->setContent('Task content');
        $new_task->setAuthor($this->user);
        $new_task->setIsDone(false);
        $em->persist($new_task);
        $em->flush();

        // task->isDone(true)
        $task = $taskRepository->findOneBy(['author' => $this->user, 'isDone' => true]);
        $this->assertNotNull($task, 'La tâche avec le titre "Task 1" n\'existe pas.');

        $this->client->loginUser($this->user);

        $initialStatus = $task->setIsDone(true);
        $this->client->request('GET', '/tasks/' . $task->getId() . '/toggle');

        $updatedTask = $taskRepository->find($task->getId());
        $this->assertNotEquals($initialStatus, $updatedTask->isDone());

        $this->assertResponseRedirects('/tasks');

        $this->client->followRedirect();

        // task->isDone(false)
        $task = $taskRepository->findOneBy(['author' => $this->user, 'isDone' => false ]);
        $this->assertNotNull($task, 'La tâche avec le titre "Task 2" n\'existe pas.');

        $initialStatus = $task->setIsDone(true);
        $this->client->request('GET', '/tasks/' . $task->getId() . '/toggle');

        $updatedTask = $taskRepository->find($task->getId());
        $this->assertNotEquals($initialStatus, $updatedTask->isDone());

        $this->assertResponseRedirects('/tasks');

        $this->client->followRedirect();
    }

    // tasks/delete
    public function testUserCanDeleteTask(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $new_task = new Task();
        $new_task->setTitle('User Task');
        $new_task->setContent('Task content');
        $new_task->setAuthor($this->user);
        $em->persist($new_task);
        $em->flush();

        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $task = $taskRepository->findOneBy(['author' => $this->user]);

        $this->assertNotNull($task, 'La tâche à supprimer n\'a pas été trouvée.');

        $this->client->loginUser($this->user);
        $this->client->request('GET', '/tasks/' . $task->getId() . '/delete');

        $this->assertEquals($task->getId(), null);

        $this->assertResponseRedirects('/tasks');

        $this->client->followRedirect();
        $this->assertSelectorTextContains('.bg-light-green', 'La tâche a bien été supprimée.');
    }

    public function testAdminCanDeleteAnonymousTask(): void
    {
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

        $this->client->loginUser($this->admin);
        $this->client->request('GET', '/tasks/' . $task->getId() . '/delete');

        $this->assertEquals($task->getId(), null);

        $this->assertResponseRedirects('/tasks');

        $this->client->followRedirect();
        $this->assertSelectorTextContains('.bg-light-green', 'La tâche a bien été supprimée.');
    }

    public function testUserCannotDeleteTask(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $new_task = new Task();
        $new_task->setTitle('User Task');
        $new_task->setContent('Task content');
        $new_task->setAuthor($this->admin);
        $em->persist($new_task);
        $em->flush();

        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $task = $taskRepository->findOneBy(['author' => $this->admin]);

        $this->assertNotNull($task, 'La tâche à supprimer n\'a pas été trouvée.');

        $this->client->loginUser($this->user);
        $this->client->request('GET', '/tasks/' . $task->getId() . '/delete');

        $this->assertResponseStatusCodeSame(403);
    }
}
