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
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $oneMonthAgo = (clone $now)->modify('-1 month');
        $em = $this->entityManager;

        $etats = $em->getRepository(Etat::class)->findAll();
        $etatMap = [];
        foreach ($etats as $etat) {
            $etatMap[$etat->getLibelle()] = $etat;
        }

        $clotureeQuery = $em->createQuery(
            'UPDATE App\Entity\Sortie s 
            SET s.etat = :etatCloturee 
            WHERE (s.registerLimit <= SIZE(s.users) OR s.dateLimite <= :now)
            AND s.etat != :etatCloturee
            AND s.etat != :etatTerminee
            AND s.etat != :etatAnnulee'
        )
            ->setParameter('etatCloturee', $etatMap['Clôturée'])
            ->setParameter('now', $now)
            ->setParameter('etatTerminee', $etatMap['Terminée'])
            ->setParameter('etatAnnulee', $etatMap['Annulée']);


        $numUpdatedCloturee = $clotureeQuery->execute();

        $termineeQuery = $em->createQuery(
            'UPDATE App\Entity\Sortie s 
                 SET s.etat = :etatTerminee 
                 WHERE (DATE_ADD(s.dateHeureDebut, s.duration, \'MINUTE\')) < :now
                 AND s.etat = :etatCloturee
                 AND s.etat != :etatAnnulee'
        )
            ->setParameter('etatTerminee', $etatMap['Terminée'])
            ->setParameter('now', $now)
            ->setParameter('etatCloturee', $etatMap['Clôturée'])
            ->setParameter('etatAnnulee', $etatMap['Annulée']);

        $numUpdatedTerminee = $termineeQuery->execute();

        $historiseeQuery = $em->createQuery(
            'UPDATE App\Entity\Sortie s
                 SET s.etat = :etatHistorisee
                 WHERE s.dateHeureDebut <= :oneMonthAgo
                 AND s.etat != :etatHistorisee'
        )
            ->setParameter('etatHistorisee', $etatMap['Historisée'])
            ->setParameter('oneMonthAgo', $oneMonthAgo);

        $numUpdatedHistorisee = $historiseeQuery->execute();



        // Return the total number of updated sorties
        return $numUpdatedCloturee + $numUpdatedTerminee + $numUpdatedHistorisee;
    }

}