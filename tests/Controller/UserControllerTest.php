<?php

namespace App\Tests\Controller;

use App\DataFixtures\TasksFixtures;
use App\DataFixtures\UsersFixtures;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class UserControllerTest extends WebTestCase
{
    protected ?AbstractDatabaseTool $databaseTool = null;
    private KernelBrowser|null $client = null;
    private UserRepository|null $userRepository = null;
    private User|null $admin = null;
    private User|null $user = null;
    private User|null $userTest = null;

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
        $this->userTest = $this->userRepository->findOneByEmail('user@test.com');

        $this->assertNotNull($this->admin, 'Aucun utilisateur avec le rôle ROLE_ADMIN n\'a été trouvé dans la base de données.');
        $this->assertNotNull($this->user, 'Aucun utilisateur avec le rôle ROLE_USER n\'a été trouvé dans la base de données.');
        $this->assertNotNull($this->userTest, 'Aucun utilisateur TEST n\'a été trouvé dans la base de données.');
    }

    // /users
    public function testUserListIsAccessibleByAdmin(): void
    {
        $this->client->loginUser($this->admin);

        $this->client->request('GET', '/users');

        $this->assertResponseIsSuccessful();
    }

    public function testUserListIsInaccessibleByNonAdmin(): void
    {
        $this->client->loginUser($this->user);

        $this->client->request('GET', '/users');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUserListRedirectsForAnonymousUser(): void
    {
        $this->client->request('GET', '/users');

        $this->assertResponseRedirects('/login');
    }

    // /users/create
    public function testCreateUserIsAccessibleByAdmin(): void
    {
        $this->client->loginUser($this->admin);

        $crawler = $this->client->request('GET', '/users/create');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="user[username]"]');
        $this->assertSelectorExists('input[name="user[email]"]');
        $this->assertSelectorExists('input[name="user[password][first]"]');
        $this->assertSelectorExists('input[name="user[password][second]"]');
        $this->assertSelectorExists('input[name="user[roles][]"][value="ROLE_USER"]');
        $this->assertSelectorExists('input[name="user[roles][]"][value="ROLE_ADMIN"]');

        $form = $crawler->selectButton('Ajouter')->form([
            'user[username]' => 'newuser',
            'user[email]' => 'user@new.com',
            'user[password][first]' => 'Password123@',
            'user[password][second]' => 'Password123@',
            'user[roles]' => ['ROLE_USER']
        ]);

        $this->client->submit($form);

        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $newUser = $entityManager->getRepository(User::class)->findOneBy(['username' => 'newuser']);
        $this->assertNotNull($newUser, 'L\'utilisateur n\'a pas été créé.');
        $this->assertSame('user@new.com', $newUser->getEmail());
        $this->assertSame(['ROLE_USER'], $newUser->getRoles());
    }

    public function testCreateUserIsForbiddenForNonAdmin(): void
    {
        $this->client->loginUser($this->user);

        $this->client->request('GET', '/users/create');
        $this->assertResponseStatusCodeSame(403);
    }

    // /users/edit
    public function testEditUserByAdmin(): void
    {
        $this->client->loginUser($this->admin);

        $crawler = $this->client->request('GET', '/users/' . $this->userTest->getId() . '/edit');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="user[username]"]');
        $this->assertSelectorExists('input[name="user[email]"]');
        $this->assertSelectorExists('input[name="user[roles][]"][value="ROLE_USER"]');
        $this->assertSelectorExists('input[name="user[roles][]"][value="ROLE_ADMIN"]');

        $form = $crawler->selectButton('Modifier')->form([
            'user[username]' => 'updateduser',
            'user[email]' => 'user@updated.com',
            'user[roles]' => ['ROLE_USER', 'ROLE_ADMIN']
        ]);

        $this->client->submit($form);

        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $updatedUser = $entityManager->getRepository(User::class)->findOneBy(['username' => 'updateduser']);
        $this->assertNotNull($updatedUser, 'L\'utilisateur n\'a pas été modifié.');
        $this->assertSame('user@updated.com', $updatedUser->getEmail());
        $this->assertSame(['ROLE_USER', 'ROLE_ADMIN'], $updatedUser->getRoles());
    }
}
