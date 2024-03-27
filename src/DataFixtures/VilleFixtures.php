<?php

namespace App\DataFixtures;

use App\Entity\Ville;
use App\Entity\Lieu;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class VilleFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Récupérer tous les lieux depuis la base de données
        $lieux = $manager->getRepository(Lieu::class)->findAll();

        // Si aucun lieu n'existe, on ne peut pas associer de ville
        if (empty($lieux)) {
            return;
        }

        // Création des villes avec Faker
        for ($i = 0; $i < 5; $i++) {
            $ville = new Ville();
            $ville->setNom($faker->city);
            $ville->setCodePostal($faker->postcode);
            $manager->persist($ville);

            // Associer chaque ville à un sous-ensemble aléatoire des lieux
            $lieuxAssocies = $faker->randomElements($lieux, $faker->numberBetween(1, count($lieux)));
            foreach ($lieuxAssocies as $lieu) {
                $lieu->setCity($ville);
            }
        }

        $manager->flush();
    }

    // Indique à Doctrine que cette fixture dépend de LieuFixtures
    public function getDependencies(): array
    {
        return [
            LieuFixtures::class,
        ];
    }
}