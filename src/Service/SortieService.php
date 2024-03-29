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

    public function checkAndUpdateEtatSortie(Sortie $sortie): void
    {
        $nbParticipants = count($sortie->getUsers());
        $dateLimite = $sortie->getDateLimite();
        $nbPlacesMax = $sortie->getRegisterLimit();

        $etatCloturee = $this->entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Clôturée']);
        $etatOuverte = $this->entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Ouverte']);


        if ($nbParticipants >= $nbPlacesMax || new \DateTime() >= $dateLimite) {
            $sortie->setEtat($etatCloturee);

        } else {
            if ($sortie->getEtat() !== $etatOuverte) {
                $sortie->setEtat($etatOuverte);
            }
        }

        $this->entityManager->flush();
    }
}