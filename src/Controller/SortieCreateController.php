<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Entity\Etat;
use App\Form\SortieType;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sortie/create')]
class SortieCreateController extends AbstractController
{
    #[Route('/', name: 'app_sortie_create_index', methods: ['GET'])]
    public function index(SortieRepository $sortieRepository): Response
    {
        return $this->render('sortie_create/index.html.twig', [
            'sorties' => $sortieRepository->findAll(),
        ]);
    }

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/new', name: 'app_sortie_create_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sortie = new Sortie();
        // Définir l'utilisateur connecté comme organisateur de la sortie
        $user = $this->getUser();
        $sortie->setUser($user);

        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $clickedButton = $form->getClickedButton();
            if ($clickedButton && 'publier' === $clickedButton->getName()) {
                // Définir l'état de la sortie sur "Ouverte" si le bouton "Publier" a été cliqué
                $etat = $entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Ouverte']);
                $sortie->setEtat($etat);
            } else {
                // Définir l'état de la sortie sur "En création" par défaut
                $etat = $entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'En création']);
                $sortie->setEtat($etat);
            }

            // Traitement du formulaire et enregistrement de la sortie
            $entityManager->persist($sortie);
            $entityManager->flush();

            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sortie_create/new.html.twig', [
            'sortie' => $sortie,
            'form' => $form->createView(),
        ]);
    }


    #[Route('/{id}/edit', name: 'app_sortie_create_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sortie_create/edit.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_sortie_create_delete', methods: ['POST'])]
    public function delete(Request $request, Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$sortie->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($sortie);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
    }

}