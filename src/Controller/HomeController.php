<?php

namespace App\Controller;

use App\Entity\Campus;
use App\Entity\Etat;
use App\Entity\Sortie;
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
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security) // Injection de Security ici
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
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
        // Je récupère le campus de l'utilisateur connecté
        $selectedCampus = null;
        $selectedCampusNom = $request->query->get('campus');
        $nomRecherche = $request->query->get('nom');
        $date1 = $request->query->get('date1');
        $date2 = $request->query->get('date2');
        $estOrganisateur = $request->query->get('organisateur');
        $estTerminees = $request->query->get('terminees');
        $campuses = $this->entityManager->getRepository(Campus::class)->findAll();
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');

        $qb = $this->entityManager->getRepository(Sortie::class)->createQueryBuilder('s');

        if ($selectedCampusNom) {
            $selectedCampus = $this->entityManager->getRepository(Campus::class)->findOneBy(['nom' => $selectedCampusNom]);
            $qb->andWhere('s.place = :selectedCampus')
                ->setParameter('selectedCampus', $selectedCampus);
        }

        if ($nomRecherche) {
            $qb->andWhere('s.nom LIKE :nomRecherche')
                ->setParameter('nomRecherche', '%' . $nomRecherche . '%');
        }

        if ($date1 && $date2) {
            // Convertir les chaînes de date en objets DateTime
            $date1Obj = new \DateTime($date1);
            $date2Obj = new \DateTime($date2);

            // Ajouter un jour à la date de fin pour inclure les sorties du jour spécifié
            $date2Obj->modify('+1 day');

            $qb->andWhere('s.dateHeureDebut BETWEEN :date1 AND :date2')
                ->setParameter('date1', $date1Obj)
                ->setParameter('date2', $date2Obj);
        }

        if ($estOrganisateur) {
            $qb->andWhere('s.user = :user')
                ->setParameter('user', $user);
        }

        if ($estTerminees) {
            // Je récupère l'objet Etat correspondant à "Terminée"
            $etatTerminee = $this->entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Terminée']);

            if ($etatTerminee) {
                // Si l'objet Etat est trouvé, je récupère les sorties avec cet état
                $qb->andWhere('s.etat = :etatTerminee')
                    ->setParameter('etatTerminee', $etatTerminee);
            } else {
                // Gérer le cas où l'état "Terminée" n'est pas trouvé
                $sorties = [];
            }
        }

        $sorties = $qb->getQuery()->getResult();

        $date1 = null;
        $date2 = null;

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'campuses' => $campuses,
            'isAdmin' => $isAdmin,
            'sorties' => $sorties,
            'selectedCampus' => $selectedCampus, // Transmettre le campus sélectionné au template
            'user' => $user, // Transmettre l'utilisateur connecté au template
            'date1' => $date1,
            'date2' => $date2,
        ]);
    }
}