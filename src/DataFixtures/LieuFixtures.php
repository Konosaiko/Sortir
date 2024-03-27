<?php

// LieuFixtures.php

namespace App\DataFixtures;

use App\Entity\Lieu;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class LieuFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Tableau pour stocker les références vers les lieux créés
        $lieuxReferences = [];

        // Création des lieux avec Faker
        for ($i = 0; $i < 10; $i++) { // Créer 10 lieux de démonstration, ajustez le nombre selon vos besoins
            $lieu = new Lieu();
            $lieu->setNom($faker->company); // Générer un nom de lieu aléatoire
            $lieu->setAddress($faker->address); // Générer une adresse aléatoire
            $lieu->setLatitude($faker->latitude); // Générer une latitude aléatoire
            $lieu->setLongitude($faker->longitude); // Générer une longitude aléatoire
            // Associer éventuellement un lieu à une ville si nécessaire
            // $lieu->setCity($ville);
            $manager->persist($lieu);

            // Ajout de la référence au tableau
            $lieuxReferences[] = $lieu;
        }

        $manager->flush();

        // Enregistrement des références pour pouvoir les réutiliser dans d'autres fixtures
        foreach ($lieuxReferences as $index => $lieu) {
            $this->addReference('lieu_' . $index, $lieu);
        }
    }
}
