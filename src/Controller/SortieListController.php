<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Entity\Campus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SortieListController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'app_sortie_list')]
    public function index(Request $request): Response
    {
        $selectedCampusId = $request->query->get('campus');

        $selectedCampusId = $selectedCampusId ?: '';

        if ($selectedCampusId) {
            $sorties = $this->entityManager->getRepository(Sortie::class)->findBy(['place' => $selectedCampusId]);
        } else {
            // Si aucun campus n'est sélectionné, afficher toutes les sorties
            $sorties = $this->entityManager->getRepository(Sortie::class)->findAll();
        }

        $campuses = $this->entityManager->getRepository(Campus::class)->findAll();

        return $this->render('sortie/sortie.html.twig', [
            'sorties' => $sorties,
            'campuses' => $campuses,
        ]);
    }
}