<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies()
    {
        return [
            CampusFixtures::class,
        ];
    }
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }


    public function load(ObjectManager $manager): void
    {
        // Récupérer tous les campus depuis la base de données
        $campuses = $manager->getRepository(Campus::class)->findAll();

        // Choisir un campus au hasard parmi ceux disponibles
        $randomCampus = $campuses[array_rand($campuses)];

        // Créer l'utilisateur admin
        $user = new User();
        $user->setEmail('admin@admin.fr');
        $user->setUsername('admin');
        $user->setPassword($this->passwordHasher->hashPassword(
            $user,
            '123456'
        ));
        $user->setFirstName('admin');
        $user->setName('admin');
        $user->setPhone('0652526132');
        $user->setIsAttachedTo($randomCampus);
        $user->setRoles(['ROLE_ADMIN']);

        // Créer un deuxième utilisateur test
        $user2 = new User();
        $user2->setUsername('user');
        $user2->setEmail('user.user.fr');
        $user2->setFirstName('user');
        $user2->setName('user');
        $user2->setPhone('0652526132');
        $user2->setIsAttachedTo($randomCampus);
        $user2->setRoles(['ROLE_USER']);
        $user2->setPassword($this->passwordHasher->hashPassword(
            $user2,
            '123456'
        ));

        // Persister et flusher les utilisateurs
        $manager->persist($user);
        $manager->persist($user2);
        $manager->flush();
    }
}
