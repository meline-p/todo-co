<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TaskControllerTest extends WebTestCase
{
    public function testListWithoutFilter()
    {
        // // Créez le client de test
        // $client = static::createClient();

        // // Obtenez le service UserPasswordHasherInterface pour hasher le mot de passe
        // $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        // // Créez un utilisateur
        // $user = new User();
        // $user->setUsername('testuser');
        // $user->setEmail('test@test.com');

        // // Hachez le mot de passe avant de l'attribuer à l'utilisateur
        // $user->setPassword($passwordHasher->hashPassword($user, 'password'));

        // // Assurez-vous que l'utilisateur a le rôle ROLE_USER
        // $user->setRoles(['ROLE_USER']);

        // // Persistez l'utilisateur dans la base de données de test (si nécessaire)
        // $entityManager = self::getContainer()->get('doctrine')->getManager();
        // $entityManager->persist($user);
        // $entityManager->flush();

        // // Connectez l'utilisateur
        // $client->loginUser($user);

        // // Faites une requête GET pour accéder à la liste des tâches
        // $client->request('GET', '/tasks');

        // // Vérifiez que la réponse a un statut 200 (OK)
        // $this->assertResponseStatusCodeSame(200);

        // // Vérifiez que la page contient le titre 'Task List'
        // $this->assertSelectorTextContains('h1', 'Task List');
    }
}
