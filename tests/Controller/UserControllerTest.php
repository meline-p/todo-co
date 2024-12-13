<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    // /users
    public function testUserListIsAccessibleByAdmin(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $adminUser = $userRepository->findOneByEmail('toto@toto.fr');

        $this->assertNotNull($adminUser, 'Aucun utilisateur avec le rôle ROLE_ADMIN n\'a été trouvé dans la base de données.');

        $client->loginUser($adminUser);

        $client->request('GET', '/users');

        $this->assertResponseIsSuccessful();
    }

    public function testUserListIsInaccessibleByNonAdmin(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $regularUser = $userRepository->findOneByEmail('john@doe.com');

        $this->assertNotNull($regularUser, 'Aucun utilisateur avec le rôle ROLE_USER n\'a été trouvé dans la base de données.');

        $client->loginUser($regularUser);

        $client->request('GET', '/users');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUserListRedirectsForAnonymousUser(): void
    {
        $client = static::createClient();

        $client->request('GET', '/users');

        $this->assertResponseRedirects('/login');
    }

    // /users/create
    public function testCreateUserIsAccessibleByAdmin(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $adminUser = $userRepository->findOneByEmail('toto@toto.fr');
        $this->assertNotNull($adminUser, 'Aucun utilisateur avec le rôle ROLE_ADMIN n\'a été trouvé dans la base de données.');

        $client->loginUser($adminUser);

        $crawler = $client->request('GET', '/users/create');
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
            'user[email]' => 'newuser@example.com',
            'user[password][first]' => 'Password123@',
            'user[password][second]' => 'Password123@',
            'user[roles]' => ['ROLE_USER']
        ]);

        $client->submit($form);

        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $newUser = $entityManager->getRepository(User::class)->findOneBy(['username' => 'newuser']);
        $this->assertNotNull($newUser, 'L\'utilisateur n\'a pas été créé.');
        $this->assertSame('newuser@example.com', $newUser->getEmail());
        $this->assertSame(['ROLE_USER'], $newUser->getRoles());
    }

    public function testCreateUserIsForbiddenForNonAdmin(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $regularUser = $userRepository->findOneByEmail('john@doe.com');
        $this->assertNotNull($regularUser, 'Aucun utilisateur avec le rôle ROLE_USER n\'a été trouvé dans la base de données.');

        $client->loginUser($regularUser);

        $client->request('GET', '/users/create');
        $this->assertResponseStatusCodeSame(403);
    }

    // /users/edit
    public function testEditUserByAdmin(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $adminUser = $userRepository->findOneByEmail('toto@toto.fr');
        $this->assertNotNull($adminUser, 'Aucun utilisateur avec le rôle ROLE_ADMIN n\'a été trouvé dans la base de données.');

        $client->loginUser($adminUser);

        $userToEdit = $userRepository->findOneByEmail('test@test.fr');
        $this->assertNotNull($userToEdit, 'Aucun utilisateur trouvé à éditer.');

        $crawler = $client->request('GET', '/users/' . $userToEdit->getId() . '/edit');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="user[username]"]');
        $this->assertSelectorExists('input[name="user[email]"]');
        $this->assertSelectorExists('input[name="user[roles][]"][value="ROLE_USER"]');
        $this->assertSelectorExists('input[name="user[roles][]"][value="ROLE_ADMIN"]');

        $form = $crawler->selectButton('Modifier')->form([
            'user[username]' => 'updateduser',
            'user[email]' => 'updateduser@example.com',
            'user[roles]' => ['ROLE_USER', 'ROLE_ADMIN']
        ]);

        $client->submit($form);

        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $updatedUser = $entityManager->getRepository(User::class)->findOneBy(['username' => 'updateduser']);
        $this->assertNotNull($updatedUser, 'L\'utilisateur n\'a pas été modifié.');
        $this->assertSame('updateduser@example.com', $updatedUser->getEmail());
        $this->assertSame(['ROLE_USER', 'ROLE_ADMIN'], $updatedUser->getRoles());
    }
}
