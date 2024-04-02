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
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

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
            $dateHeureDebut = $sortie->getDateHeureDebut();
            $dateLimite = $sortie->getDateLimite();

            if ($dateHeureDebut <= $dateLimite) {
                // Afficher un message d'erreur
                $this->addFlash('error', 'La date limite doit être postérieure à la date de début.');
                return $this->redirectToRoute('app_sortie_create_new'); // Rediriger où vous le souhaitez
            } else {
                // Si les dates sont valides, continuer avec le traitement normal du formulaire
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

    #[Route('/{id}/publish', name: 'app_sortie_create_publish', methods: ['GET'])]
    public function publish(Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        $etatOuverte = $entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Ouverte']);
        $sortie->setEtat($etatOuverte);
        $entityManager->flush();

        $this->addFlash('success', 'La sortie a été publiée avec succès.');

        return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/cancel', name: 'app_sortie_create_cancel', methods: ['POST'])]
    public function cancel(Request $request, Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        // Vérifiez si le formulaire a été soumis
        if ($request->isMethod('POST')) {
            // Récupérez le motif d'annulation à partir de la requête
            $motifAnnulation = $request->request->get('motifAnnulation');

            // Vérifiez si le token CSRF est valide
            if ($this->isCsrfTokenValid('cancel' . $sortie->getId(), $request->request->get('_token'))) {
                // Vérifiez si le motif d'annulation n'est pas vide
                if (!empty($motifAnnulation)) {
                    // Stockez le motif d'annulation dans la sortie
                    $sortie->setMotifAnnulation($motifAnnulation);

                    // Mettez à jour l'état de la sortie
                    $etatAnnulee = $entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Annulée']);
                    $sortie->setEtat($etatAnnulee);

                    // Enregistrez les modifications dans la base de données
                    $entityManager->flush();

                    // Ajoutez un message flash pour confirmer l'annulation de la sortie
                    $this->addFlash('success', 'La sortie a été annulée avec succès.');
                } else {
                    // Si le motif d'annulation est vide, ajoutez un message d'erreur
                    $this->addFlash('error', 'Veuillez spécifier un motif d\'annulation.');
                }
            } else {
                // Si le token CSRF est invalide, ajoutez un message d'erreur
                $this->addFlash('error', 'Token CSRF invalide.');
            }
        }

        // Redirigez l'utilisateur vers la page d'accueil ou une autre page appropriée
        return $this->redirectToRoute('app_home');
    }

}