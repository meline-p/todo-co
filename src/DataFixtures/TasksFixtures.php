<?php

namespace App\DataFixtures;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class TasksFixtures extends Fixture implements DependentFixtureInterface
{
    private $counter = 1;
    private $cachePool;

    public function __construct(TagAwareCacheInterface $cachePool)
    {
        $this->cachePool = $cachePool;
    }

    public function load(ObjectManager $manager): void
    {
        $this->cachePool->invalidateTags(['task_list']);

        $this->createTask(
            "Finaliser le rapport d'audit",
            "Compléter la section sur la dette technique et relire le document avant soumission.",
            $manager
        );

        $this->createTask(
            "Réserver une salle de réunion",
            "Vérifier les disponibilités et réserver une salle pour la présentation de lundi matin.",
            $manager
        );

        $this->createTask(
            "Corriger les bugs sur l'application",
            "Résoudre les erreurs de connexion pour les utilisateurs non authentifiés.",
            $manager
        );

        $this->createTask(
            "Mettre à jour le serveur de production",
            "Appliquer les derniers correctifs de sécurité et redémarrer les services.",
            $manager
        );

        $this->createTask(
            "Créer une maquette pour la page d'accueil",
            "Proposer un nouveau design basé sur le feedback des utilisateurs.",
            $manager
        );

        $this->createTask(
            "Écrire une documentation utilisateur",
            "Rédiger un guide sur les fonctionnalités principales de l'application.",
            $manager
        );

        $this->createTask(
            "Rechercher de nouveaux outils SEO",
            "Analyser les options pour améliorer le référencement du site.",
            $manager
        );

        $this->createTask(
            "Optimiser la base de données",
            "Identifier et supprimer les index inutilisés pour améliorer les performances.",
            $manager
        );

        $this->createTask(
            "Planifier une session de brainstorming",
            "Organiser une réunion pour discuter des prochaines fonctionnalités.",
            $manager
        );

        $this->createTask(
            "Tester la nouvelle version mobile",
            "Vérifier les compatibilités sur différents appareils et systèmes d'exploitation.",
            $manager
        );

        $manager->flush();
    }

    public function createTask(string $title, string $content, ObjectManager $manager): Task
    {
        $task = new Task();
        $task->setTitle($title);
        $task->setContent($content);

        if (rand(0, 1) === 1) {
            $user = $this->getReference('usr-'.rand(1, 7), User::class);
            $task->setAuthor($user);
        } else {
            $task->setAuthor(null);
        }

        if (rand(0, 1) === 1) {
            $task->setIsDone(true);
        } else {
            $task->setIsDone(false);
        }

        $this->counter++;

        $manager->persist($task);

        return $task;
    }

    public function getDependencies(): array
    {
        return [
            UsersFixtures::class,
        ];
    }
}
