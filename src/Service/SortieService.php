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
        $oneMonthAgo = (clone $now)->modify('-1 month');
        // Get the EntityManager
        $em = $this->entityManager;

        // Retrieve the Etat 'Clôturée'
        $etatCloturee = $em->getRepository(Etat::class)->findOneBy(['libelle' => 'Clôturée']);
        // Retrieve the Etat 'Ouverte'
        $etatOuverte = $em->getRepository(Etat::class)->findOneBy(['libelle' => 'Ouverte']);

        $etatEnCreation = $em->getRepository(Etat::class)->findOneBy(['libelle' => 'En création']);

        $etatTerminee = $em->getRepository(Etat::class)->findOneBy(['libelle' => 'Terminée']);

        $etatAnnulee = $em->getRepository(Etat::class)->findOneBy(['libelle' => 'Annulée']);

        $etatHistorisee =  $em->getRepository(Etat::class)->findOneBy(['libelle' => 'Historisée']);

        // Update sorties to 'Clôturée' if conditions are met
        $clotureeQuery = $em->createQuery(
            'UPDATE App\Entity\Sortie s 
            SET s.etat = :etatCloturee 
            WHERE (s.registerLimit <= SIZE(s.users) OR s.dateLimite <= :now)
            AND s.etat != :etatCloturee
            AND s.etat != :etatTerminee
            AND s.etat != :etatAnnulee' // Ajout de cette condition
        )
            ->setParameter('etatCloturee', $etatCloturee)
            ->setParameter('now', $now)
            ->setParameter('etatTerminee', $etatTerminee)
            ->setParameter('etatAnnulee', $etatAnnulee);// Ajout du paramètre etatTerminee

        // Execute update query for 'Clôturée' sorties
        $numUpdatedCloturee = $clotureeQuery->execute();

        // Update sorties to 'Ouverte' if conditions are met
        $ouverteQuery = $em->createQuery(
            'UPDATE App\Entity\Sortie s 
        SET s.etat = :etatOuverte 
        WHERE ((s.registerLimit > SIZE(s.users) AND s.dateLimite > :now) AND s.etat != :etatOuverte) 
        AND s.etat != :etatEnCreation
        AND s.etat != :etatAnnulee'
        )
            ->setParameter('etatOuverte', $etatOuverte)
            ->setParameter('now', $now)
            ->setParameter('etatEnCreation', $etatEnCreation)
            ->setParameter('etatAnnulee', $etatAnnulee);

        // Execute update query for 'Ouverte' sorties
        $numUpdatedOuverte = $ouverteQuery->execute();

        $termineeQuery = $em->createQuery(
            'UPDATE App\Entity\Sortie s 
    SET s.etat = :etatTerminee 
    WHERE (DATE_ADD(s.dateHeureDebut, s.duration, \'MINUTE\')) < :now
    AND s.etat = :etatCloturee
        AND s.etat != :etatAnnulee'
        )
            ->setParameter('etatTerminee', $etatTerminee)
            ->setParameter('now', $now)
            ->setParameter('etatCloturee', $etatCloturee)
            ->setParameter('etatAnnulee', $etatAnnulee);

        $numUpdatedTerminee = $termineeQuery->execute();

         $historiseeQuery = $em->createQuery(
             'UPDATE App\Entity\Sortie s
            SET s.etat = :etatHistorisee
            WHERE s.dateHeureDebut <= :oneMonthAgo
            AND s.etat != :etatHistorisee'
         )
             ->setParameter('etatHistorisee', $etatHistorisee)
             ->setParameter('oneMonthAgo', $oneMonthAgo);

        $numUpdatedHistorisee = $historiseeQuery->execute();



        // Return the total number of updated sorties
        return $numUpdatedCloturee + $numUpdatedOuverte + $numUpdatedTerminee + $numUpdatedHistorisee;
    }

}