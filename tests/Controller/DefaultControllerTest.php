<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\DataFixtures\TasksFixtures;
use App\DataFixtures\UsersFixtures;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class DefaultControllerTest extends WebTestCase
{
    protected ?AbstractDatabaseTool $databaseTool = null;
    private KernelBrowser|null $client = null;

    public function setUp(): void
    {
        $this->client = static::createClient();

        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        $this->databaseTool->loadFixtures([
            UsersFixtures::class,
            TasksFixtures::class,
        ]);
    }

    public function testHomepageIsAccessible(): void
    {
        $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('h1', "Bienvenue sur Todo List, l'application vous permettant de gérer l'ensemble de vos tâches sans effort !");
    }
}
