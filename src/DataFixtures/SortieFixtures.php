<?php

namespace App\DataFixtures;

use App\Entity\Sortie;
use App\Entity\Etat;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class SortieFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [
            AppFixtures::class,
        ];
    }
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Création des états
        $etats = ['En création', 'Ouverte', 'Clôturée', 'En cours', 'Terminée', 'Annulée', 'Historisée'];
        foreach ($etats as $index => $etatName) {
            $etat = new Etat();
            $etat->setLibelle($etatName);
            $manager->persist($etat);
            $this->setReference('etat_' . $index, $etat); // Utilisation de setReference
        }
        $manager->flush();

        // Création des sorties
        for ($i = 0; $i < 20; $i++) {
            $sortie = new Sortie();
            $users = $manager->getRepository(User::class)->findAll();
            $randomUser = $users[array_rand($users)];
            $sortie->setUser($randomUser);
            $campus = $randomUser->getCampus();
            $sortie->setPlace($campus);
            $sortie->setNom($faker->sentence($nbWords = 6, $variableNbWords = true));
            $sortie->setDateHeureDebut($faker->dateTimeBetween('+2 days', '+1 week')); // Entre 2 jours et 1 semaine à partir de maintenant
            $durationInMinutes = $faker->numberBetween(1, 1440); // valeur en minutes
            $sortie->setDuration($durationInMinutes); // $durationInMinutes "minutes"
            $sortie->setDateLimite($faker->dateTimeBetween('-1 week', '+1 day')); // Entre 1 semaine avant et 1 jour après maintenant
            $sortie->setRegisterLimit($faker->numberBetween(1, 100)); // Nombre d'inscriptions limité entre 1 et 100
            $sortie->setInfos($faker->paragraph($nbSentences = 3, $variableNbSentences = true));

            // Récupérer un état aléatoire
            $etat = $this->getReference('etat_' . $faker->numberBetween(0, count($etats) - 1));
            $sortie->setEtat($etat);

            // Récupérer un lieu aléatoire parmi les références enregistrées
            $lieu = $this->getReference('lieu_' . $faker->numberBetween(0, 9));
            $sortie->setAddress($lieu);

            $manager->persist($sortie);
        }

        $manager->flush();
    }
}
