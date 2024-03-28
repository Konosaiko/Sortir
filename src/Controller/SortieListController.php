<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Entity\Campus;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


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

        $nom = $request->query->get('nom');

        $criteria = [];

        if ($nom) {
            $criteria['nom'] = $nom;
        }

        if ($selectedCampusId) {
            $criteria['place'] = $selectedCampusId;
        }

        $sorties = $this->entityManager->getRepository(Sortie::class)->findBy($criteria, ['nom' => 'ASC']);

        $campuses = $this->entityManager->getRepository(Campus::class)->findAll();

        return $this->render('sortie/sortie.html.twig', [
            'sorties' => $sorties,
            'campuses' => $campuses,
        ]);
    }

    #[Route('/inscription/{id}', name: 'app_sortie_inscription')]
    public function register(int $id, EntityManagerInterface $entityManager, SortieRepository $sortieRepository): Response
    {
        $sortie = $sortieRepository->find($id);
        $user = $this->getUser();

        if (!$sortie) {
            $this->addFlash('error', 'La sortie demandée n\'existe pas.');
            return $this->redirectToRoute('app_sortie_list');
        }

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour vous inscrire à une sortie.');
            return $this->redirectToRoute('app_login');
        }

        if ($sortie->getEtat()->getLibelle() !== 'Ouverte') {
            $this->addFlash('error', 'Cette sortie n\'est pas ouverte aux inscriptions.');
            return $this->redirectToRoute('app_sortie_list');
        }

        if ($sortie->getDateLimite() < new \DateTime()) {
            $this->addFlash('error', 'La date limite d\'inscription est dépassée.');
            return $this->redirectToRoute('app_sortie_list');
        }

        if ($sortie->getUsers()->contains($user)) {
            $this->addFlash('error', 'Vous êtes déjà inscrit à cette sortie.');
            return $this->redirectToRoute('app_sortie_list');
        }

        $sortie->addUser($user);
        $entityManager->flush();

        $this->addFlash('success', 'Votre inscription à la sortie a été enregistrée.');
        return $this->redirectToRoute('app_sortie_list');
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
            return $this->redirectToRoute('app_sortie_list');
        }

        if (!$sortie->getUsers()->contains($user)) {
            $this->addFlash('error', 'Vous n\'êtes pas inscrit à cette sortie.');
            return $this->redirectToRoute('app_sortie_list');
        }

        $sortie->removeUser($user);
        $entityManager->flush();

        $this->addFlash('success', 'Vous avez été désinscrit de la sortie.');
        return $this->redirectToRoute('app_sortie_list');
    }


}