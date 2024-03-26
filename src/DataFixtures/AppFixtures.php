<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }


    public function load(ObjectManager $manager): void
    {
        // Créer un campus test
        $campus = new Campus();
        $campus->setNom('Rennes');
        $manager->persist($campus);

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
        $user->setIsAttachedTo($campus);
        $user->setRoles(['ROLE_ADMIN']);
        $manager->persist($user);

        $manager->flush();
    }
}
