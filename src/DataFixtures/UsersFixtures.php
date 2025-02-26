<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class UsersFixtures extends Fixture
{
    private $counter = 1;
    private $cachePool;

    public function __construct(
        private UserPasswordHasherInterface $passwordEncoder,
        private SluggerInterface $slugger,
        TagAwareCacheInterface $cachePool
    ) {
        $this->cachePool = $cachePool;
    }

    public function load(ObjectManager $manager): void
    {
        $this->cachePool->invalidateTags(['user_list']);

        $faker = Faker\Factory::create('fr_FR');

        $roles = [
            'user' => ['ROLE_USER'],
            'admin' => ['ROLE_ADMIN','ROLE_USER']
        ];

        $new_user = new User();
        $new_user->setEmail('user@user.com');
        $new_user->setUsername('johndoe');
        $new_user->setRoles(['ROLE_USER']);
        $new_user->setPassword(
            $this->passwordEncoder->hashPassword($new_user, 'secret')
        );
        $manager->persist($new_user);
        $this->addReference('usr-1', $new_user);

        $new_user = new User();
        $new_user->setEmail('user@admin.com');
        $new_user->setUsername('toto');
        $new_user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $new_user->setPassword(
            $this->passwordEncoder->hashPassword($new_user, 'secret')
        );
        $manager->persist($new_user);
        $this->addReference('usr-2', $new_user);

        $new_user = new User();
        $new_user->setEmail('user@test.com');
        $new_user->setUsername('test');
        $new_user->setRoles(['ROLE_USER']);
        $new_user->setPassword(
            $this->passwordEncoder->hashPassword($new_user, 'secret')
        );
        $manager->persist($new_user);
        $this->addReference('usr-3', $new_user);

        for ($usr = 4; $usr <= 8; $usr++) {
            $user = new User();
            $user->setEmail($faker->email());
            $username = explode('@', $faker->email());
            $user->setUsername($this->slugger->slug($username[0])->lower());

            $randomRole = $roles[array_rand($roles)];
            $user->setRoles($randomRole);

            $user->setPassword(
                $this->passwordEncoder->hashPassword($new_user, 'secret')
            );

            $manager->persist($user);

            // ajouter une référence user
            $this->addReference('usr-'.$usr, $user);
            $this->counter++;
        }

        $manager->flush();
    }
}
