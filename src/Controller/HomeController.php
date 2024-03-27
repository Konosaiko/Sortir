<?php

namespace App\Controller;

use App\Entity\Campus;
use App\Entity\Sortie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class HomeController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security) // Injection de Security ici
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    #[Route('/', name: 'app_home')]
    public function index(Request $request, UserInterface $user): Response
    {
        // Récupérer le campus de l'utilisateur connecté
        $selectedCampusNom = $user->getCampus();

        $selectedCampusNom = $request->query->get('campus');

        $selectedCampus = null;
        if ($selectedCampusNom) {
            $selectedCampus = $this->entityManager->getRepository(Campus::class)->findOneBy(['nom' => $selectedCampusNom]);
        }

        if (!$this->security->isGranted('ROLE_USER')) {
            // Redirige vers la page de connexion
            return new RedirectResponse($this->generateUrl('app_login'));
        }

        $campuses = $this->entityManager->getRepository(Campus::class)->findAll();
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');
        $sorties = [];

        $nomRecherche = $request->query->get('nom'); // Récupérer le nom de recherche depuis la requête

        if ($selectedCampus) {
            if ($nomRecherche) {
                // Si un nom de recherche est spécifié, filtrer les sorties par nom et campus
                $sorties = $this->entityManager->getRepository(Sortie::class)
                    ->createQueryBuilder('s')
                    ->where('s.place = :selectedCampus')
                    ->andWhere('s.nom LIKE :nomRecherche')
                    ->setParameter('selectedCampus', $selectedCampus)
                    ->setParameter('nomRecherche', '%' . $nomRecherche . '%')
                    ->getQuery()
                    ->getResult();
            } else {
                // Sinon, récupérer toutes les sorties pour le campus sélectionné
                $sorties = $this->entityManager->getRepository(Sortie::class)->findBy(['place' => $selectedCampus]);
            }
        } else {
            if ($nomRecherche) {
                // Si un nom de recherche est spécifié, filtrer toutes les sorties par nom
                $sorties = $this->entityManager->getRepository(Sortie::class)
                    ->createQueryBuilder('s')
                    ->where('s.nom LIKE :nomRecherche')
                    ->setParameter('nomRecherche', '%' . $nomRecherche . '%')
                    ->getQuery()
                    ->getResult();
            } else {
                // Sinon, récupérer toutes les sorties
                $sorties = $this->entityManager->getRepository(Sortie::class)->findAll();
            }
        }

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'campuses' => $campuses,
            'isAdmin' => $isAdmin,
            'sorties' => $sorties,
            'selectedCampus' => $selectedCampus, // Transmettre le campus sélectionné au template
        ]);
    }
}
