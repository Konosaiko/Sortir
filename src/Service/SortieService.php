<?php

namespace App\Service;

use App\Entity\Etat;
use App\Entity\Sortie;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\EtatRepository;

class SortieService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function updateSortieEtats(): int
    {
        // Get the current DateTime
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));

        // Get the EntityManager
        $em = $this->entityManager;

        // Retrieve the Etat 'Clôturée'
        $etatCloturee = $em->getRepository(Etat::class)->findOneBy(['libelle' => 'Clôturée']);
        // Retrieve the Etat 'Ouverte'
        $etatOuverte = $em->getRepository(Etat::class)->findOneBy(['libelle' => 'Ouverte']);

        // Update sorties to 'Clôturée' if conditions are met
        $clotureeQuery = $em->createQuery(
            'UPDATE App\Entity\Sortie s 
        SET s.etat = :etatCloturee 
        WHERE (s.registerLimit <= SIZE(s.users) OR s.dateLimite <= :now)
        AND s.etat != :etatCloturee'
        )
            ->setParameter('etatCloturee', $etatCloturee)
            ->setParameter('now', $now);

        // Execute update query for 'Clôturée' sorties
        $numUpdatedCloturee = $clotureeQuery->execute();

        // Update sorties to 'Ouverte' if conditions are met
        $ouverteQuery = $em->createQuery(
            'UPDATE App\Entity\Sortie s 
        SET s.etat = :etatOuverte 
        WHERE (s.registerLimit > SIZE(s.users) AND s.dateLimite > :now)
        AND s.etat != :etatOuverte'
        )
            ->setParameter('etatOuverte', $etatOuverte)
            ->setParameter('now', $now);

        // Execute update query for 'Ouverte' sorties
        $numUpdatedOuverte = $ouverteQuery->execute();

        // Return the total number of updated sorties
        return $numUpdatedCloturee + $numUpdatedOuverte;
    }

}