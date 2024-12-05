<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    public function testSomething(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneByEmail('toto@toto.fr');

        $client->loginUser($testUser);

        $client->request('GET', '/tasks');





        $this->assertResponseIsSuccessful();
        // $this->assertSelectorTextContains('h1', "Liste des tâches");
    }
}
