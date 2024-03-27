<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CampusFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $campusNames = ['Rennes', 'Nantes', 'Niort', 'Quimper', 'Online']; // Liste des noms de campus

        foreach ($campusNames as $name) {
            $campus = new Campus();
            $campus->setNom($name);
            $manager->persist($campus);
        }

        $manager->flush();
    }
}