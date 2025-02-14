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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Config\Security\PasswordHasherConfig;

class SecurityControllerTest extends WebTestCase
{
    protected ?AbstractDatabaseTool $databaseTool = null;
    private KernelBrowser|null $client = null;
    private UserRepository|null $userRepository = null;
    private User|null $admin = null;
    private User|null $user = null;
    private PasswordHasherInterface $passwordHasher;

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

    public function testLoginPageIsAccessible(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();

        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="_username"]');
        $this->assertSelectorExists('input[name="_password"]');
        $this->assertSelectorExists('button[type="submit"]');
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $this->client->request('POST', '/login', [
            '_username' => 'wrong_user',
            '_password' => 'wrong_password',
        ]);

        $this->assertResponseRedirects('/login');

        $this->client->followRedirect();

        $this->assertSelectorExists('.alert-danger');
    }

    public function testLoginWithValidCredentials(): void
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setUsername('valid_user');
        $user->setPassword($passwordHasher->hashPassword($user, 'valid_password'));
        $user->setEmail('user@valid.com');
        $user->setRoles(['ROLE_USER']);
        $entityManager->persist($user);
        $entityManager->flush();

        $valid_user = $this->userRepository->findOneByEmail('user@admin.com');
        $this->assertNotNull($valid_user, 'Aucun utilisateur n\'a été trouvé dans la base de données.');

        $this->client->request('POST', '/login', [
            '_username' => 'valid_user',
            '_password' => 'valid_password',
        ]);

        $this->client->followRedirect();
    }
}
