<?php

namespace App\Controller;

use App\Entity\Campus;
use App\Entity\Etat;
use App\Entity\Sortie;
use App\Repository\SortieRepository;
use App\Service\SortieService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class HomeController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private SortieService $sortieService;
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security, SortieService $sortieService) // Injection de Security ici
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->sortieService = $sortieService;
    }

    #[Route('/annuler-sortie/{id}', name: 'app_annuler_sortie', methods: ['POST'])]
    public function annulerSortie(Request $request, Sortie $sortie): Response
    {
        // Vérifier si la requête est une requête AJAX
        if (!$request->isXmlHttpRequest()) {
            // Rediriger vers la page d'accueil ou afficher un message d'erreur
            return $this->redirectToRoute('app_home');
        }

        // Récupérer l'état "Annulée"
        $etatAnnulee = $this->entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Annulée']);

        // Mettre à jour l'état de la sortie
        $sortie->setEtat($etatAnnulee);
        $this->entityManager->flush();

        // Retourner une réponse JSON indiquant le succès de l'opération
        return new JsonResponse(['success' => true]);
    }

    #[Route('/', name: 'app_home')]
    public function index(Request $request, UserInterface $user): Response
    {
        // Préparez les options de filtrage
        $filterOptions = [
            'campus' => $request->query->get('campus'),
            'nom' => $request->query->get('nom'),
            'date1' => $request->query->get('date1'),
            'date2' => $request->query->get('date2'),
            'organisateur' => $request->query->get('organisateur') ? $user : null,
            'terminees' => $request->query->get('terminees'),
        ];

        $campuses = $this->entityManager->getRepository(Campus::class)->findAll();
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');

        $selectedCampus = null;
        if (!empty($filterOptions['campus'])) {
            $selectedCampus = $this->entityManager->getRepository(Campus::class)->findOneBy(['nom' => $filterOptions['campus']]);
        }

        // Mise à jour des états des sorties avant de récupérer la liste
        $numUpdated = $this->sortieService->updateSortieEtats();

        // Récupérez toutes les sorties avec les options de filtrage
        $sorties = $this->entityManager->getRepository(Sortie::class)->findAllSorties($filterOptions);

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'campuses' => $campuses,
            'isAdmin' => $isAdmin,
            'sorties' => $sorties,
            'selectedCampus' => $selectedCampus,
            'user' => $user,
            'date1' => $filterOptions['date1'],
            'date2' => $filterOptions['date2'],
        ]);
    }


    #[Route('/inscription/{id}', name: 'app_sortie_inscription')]
    public function register(int $id, EntityManagerInterface $entityManager, SortieRepository $sortieRepository): Response
    {
        $sortie = $sortieRepository->find($id);
        $user = $this->getUser();

        if (!$sortie) {
            $this->addFlash('error', 'La sortie demandée n\'existe pas.');
            return $this->redirectToRoute('app_home');
        }

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour vous inscrire à une sortie.');
            return $this->redirectToRoute('app_login');
        }

        if ($sortie->getEtat()->getLibelle() !== 'Ouverte') {
            $this->addFlash('error', 'Cette sortie n\'est pas ouverte aux inscriptions.');
            return $this->redirectToRoute('app_home');
        }

        if ($sortie->getDateLimite() < new \DateTime()) {
            $this->addFlash('error', 'La date limite d\'inscription est dépassée.');
            return $this->redirectToRoute('app_home');
        }

        if ($sortie->getUsers()->contains($user)) {
            $this->addFlash('error', 'Vous êtes déjà inscrit à cette sortie.');
            return $this->redirectToRoute('app_home');
        }

        $sortie->addUser($user);
        $entityManager->flush();

        $this->addFlash('success', 'Votre inscription à la sortie a été enregistrée.');
        return $this->redirectToRoute('app_home');
    }

    #[Route('/detailsortie/{id}', name: 'app_sortie_create_show', methods: ['GET'])]
    public function show(Sortie $sortie): Response
    {
        return $this->render('sortie_create/show.html.twig', [
            'sortie' => $sortie,
        ]);
    }

    #[Route('/sortie/desistement/{id}', name: 'app_sortie_desistement')]
    public function seDesister(int $id, EntityManagerInterface $entityManager, SortieRepository $sortieRepository): Response
    {
        $sortie = $sortieRepository->find($id);
        $user = $this->getUser();

        if (!$sortie || !$user) {
            throw $this->createNotFoundException('Sortie ou Utilisateur introuvable.');
        }

        if ($sortie->getDateHeureDebut() < new \DateTime()) {
            $this->addFlash('error', 'La sortie a déjà débuté.');
            return $this->redirectToRoute('app_home');
        }

        if (!$sortie->getUsers()->contains($user)) {
            $this->addFlash('error', 'Vous n\'êtes pas inscrit à cette sortie.');
            return $this->redirectToRoute('app_home');
        }

        $sortie->removeUser($user);
        $entityManager->flush();

        $this->addFlash('success', 'Vous avez été désinscrit de la sortie.');
        return $this->redirectToRoute('app_home');
    }

}