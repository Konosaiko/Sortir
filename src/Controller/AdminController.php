<?php

namespace App\Controller;

use App\Entity\Campus;
use App\Form\CampusType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;


#[Route('/admin', name: 'admin_')]
class AdminController extends AbstractController
{
    private $entityManager;

    private $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
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
        // Vérifier si le formulaire de modification a été soumis
        if ($request->isMethod('POST')) {
            // Récupérer le nouveau nom du campus à partir des données du formulaire
            $newName = $request->request->get('newName');

            // Mettre à jour le nom du campus dans la base de données
            $campus->setNom($newName);

            // Enregistrer les modifications dans la base de données
            $entityManager = $this->entityManager;
            $entityManager->flush();

            // Rediriger l'utilisateur vers la page de gestion des campus avec un message de succès
            $this->addFlash('success', 'Le nom du campus a été modifié avec succès.');
            return $this->redirectToRoute('admin_campus');
        }
    }

    #[Route('/campus/delete/{id}', name: 'campus_delete')]
    public function deleteCampus(Request $request, Campus $campus): Response
    {
        // Supprimez le campus de la base de données
        $this->entityManager->remove($campus);
        $this->entityManager->flush();

        // Ajoutez un message flash pour confirmer la suppression
        $this->addFlash('success', 'Le campus a été supprimé avec succès.');

        // Redirigez l'utilisateur vers la page des campus
        return $this->redirectToRoute('admin_campus');
    }

    #[Route('/villes', name: 'villes')]
    public function manageVilles()
    {

    }

}
