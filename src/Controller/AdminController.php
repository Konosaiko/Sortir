<?php

namespace App\Controller;

use App\Entity\Campus;
use App\Form\CampusType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;


#[Route('/admin', name: 'admin_')]
class AdminController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    #[Route('/', name: 'dashboard')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/users', name: 'users')]
    public function manageUsers()
    {

    }
    #[Route('/campus', name: 'campus', methods: ['GET', 'POST'])]
    public function manageCampus(Request $request): Response
    {
        $campus = new Campus(); // Initialisation de la variable campus
        $form = $this->createForm(CampusType::class, $campus);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Traitement du formulaire et enregistrement du campus
            $this->entityManager->persist($campus);
            $this->entityManager->flush();

            $this->addFlash('success', 'Campus ajouté avec succès.');

            return $this->redirectToRoute('admin_campus');
        }

        // Récupérer tous les campus
        $campuses = $this->entityManager->getRepository(Campus::class)->findAll();

        return $this->render('admin/manageCampus.html.twig', [
            'form' => $form->createView(),
            'campuses' => $campuses, // Passer les campus à la vue
        ]);
    }

    #[Route('/campus/add', name: 'campus_add', methods: ['GET', 'POST'])]
    public function addCampus(Request $request): Response
    {
        $campus = new Campus(); // Initialisation du nouvel objet campus
        $form = $this->createForm(CampusType::class, $campus);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Traitement du formulaire et enregistrement du campus
            $this->entityManager->persist($campus);
            $this->entityManager->flush();

            $this->addFlash('success', 'Campus ajouté avec succès.');

            return $this->redirectToRoute('admin_campus');
        }

        return $this->render('admin/_addCampus.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/campus/edit/{id}', name: 'campus_edit')]
    public function editCampus(Request $request, Campus $campus): Response
    {
        // Votre code pour modifier un campus existant
    }

    #[Route('/campus/delete/{id}', name: 'campus_delete')]
    public function deleteCampus(Request $request, Campus $campus): Response
    {
        // Votre code pour supprimer un campus existant
    }
    #[Route('/villes', name: 'villes')]
    public function manageVilles()
    {

    }

}
