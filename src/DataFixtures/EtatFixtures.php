<?php

namespace App\DataFixtures;

use App\Entity\Etat;
use App\Entity\Sortie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EtatFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {// Récupération des sorties de la base de données
        $sorties = $manager->getRepository(Sortie::class)->findAll();

        // Parcourir chaque sortie pour obtenir l'état et son libellé
        foreach ($sorties as $sortie) {
            // Récupérer l'ID de l'état de la sortie
            $etatId = $sortie->getEtat()->getId();

            // Récupérer le libellé correspondant à partir de l'ID de l'état
            $libelle = $this->getLibelleFromSortieId($etatId);

            // Créer et persister l'instance d'Etat avec le libellé récupéré
            $etat = new Etat();
            $etat->setLibelle($libelle);
            $manager->persist($etat);
        }

        $manager->flush();
    }

    // Fonction pour obtenir le libellé de l'état à partir de son ID dans la table Sortie
    private function getLibelleFromSortieId(int $etatId): string
    {
        // Logique pour récupérer le libellé à partir de l'ID de la table Sortie
        // Vous devez implémenter la logique appropriée ici en fonction de votre structure de données

        // Par exemple, vous pouvez accéder à une autre table ou utiliser un tableau associatif
        // pour faire correspondre les ID aux libellés
        // Dans cet exemple, je vais juste retourner "En création" pour les ID pairs et "Ouverte" pour les ID impairs
        return $etatId % 2 === 0 ? 'En création' : 'Ouverte';
    }
}