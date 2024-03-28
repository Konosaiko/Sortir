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
        $selectedCampusNom = $user->getCampus();

        $selectedCampusNom = $user ? $user->getCampus() : null;

        $selectedCampusNom = $request->query->get('campus');

        $selectedCampus = null;
        if ($selectedCampusNom) {
            $selectedCampus = $this->entityManager->getRepository(Campus::class)->findOneBy(['nom' => $selectedCampusNom]);
        }

        if (!$this->security->isGranted('ROLE_USER')) {
            // Je redirige vers la page de connexion
            return new RedirectResponse($this->generateUrl('app_login'));
        }

        $campuses = $this->entityManager->getRepository(Campus::class)->findAll();
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');

        $nomRecherche = $request->query->get('nom'); // Récupérer le nom de recherche depuis la requête
        $estOrganisateur = $request->query->get('organisateur'); // Récupérer la valeur du filtre organisateur

        $estTerminees = $request->query->get('terminees'); // Récupérer la valeur du filtre sorties terminées

        $date1 = $request->query->get('date1');
        $date2 = $request->query->get('date2');


        if ($selectedCampus) {
            if ($nomRecherche) {
                if ($date1 && $date2) {
                // Convertir les chaînes de date en objets DateTime
                $date1Obj = new \DateTime($date1);
                $date2Obj = new \DateTime($date2);

                $date2Obj->modify('+1 day');

                // Je récupère les sorties où dateHeureDebut est comprise entre les deux dates
                $sorties = $this->entityManager->getRepository(Sortie::class)
                    ->createQueryBuilder('s')
                    ->where('s.dateHeureDebut BETWEEN :date1 AND :date2')
                    ->setParameter('date1', $date1Obj)
                    ->setParameter('date2', $date2Obj)
                    ->getQuery()
                    ->getResult();
            }
                // Si recherche par nom spécifié, je filtre les sorties par nom et campus
                $sorties = $this->entityManager->getRepository(Sortie::class)
                    ->createQueryBuilder('s')
                    ->where('s.place = :selectedCampus')
                    ->andWhere('s.nom LIKE :nomRecherche')
                    ->setParameter('selectedCampus', $selectedCampus)
                    ->setParameter('nomRecherche', '%' . $nomRecherche . '%')
                    ->getQuery()
                    ->getResult();
            } else {
                // Sinon, je récupére toutes les sorties pour le campus sélectionné
                $sorties = $this->entityManager->getRepository(Sortie::class)->findBy(['place' => $selectedCampus]);
            }
        } else {
            if ($nomRecherche) {
                // Si recherche par nom spécifié, je filtre toutes les sorties par nom
                $sorties = $this->entityManager->getRepository(Sortie::class)
                    ->createQueryBuilder('s')
                    ->where('s.nom LIKE :nomRecherche')
                    ->setParameter('nomRecherche', '%' . $nomRecherche . '%')
                    ->getQuery()
                    ->getResult();
            } else {
                // Sinon, je récupère toutes les sorties
                $sorties = $this->entityManager->getRepository(Sortie::class)->findAll();
            }
        }

        if ($estOrganisateur) {
            // Je récupère les sorties où l'utilisateur connecté est l'organisateur
            $sorties = $this->entityManager->getRepository(Sortie::class)->findBy(['user' => $user]);

        }

        if ($estTerminees) {
            // Je récupère l'objet Etat correspondant à "Terminée"
            $etatTerminee = $this->entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Terminée']);

            if ($etatTerminee) {
                // Si l'objet Etat est trouvé, je récupère les sorties avec cet état
                $sorties = $this->entityManager->getRepository(Sortie::class)->findBy(['etat' => $etatTerminee]);
            } else {
                // Gérer le cas où l'état "Terminée" n'est pas trouvé
                $sorties = [];
            }
        }

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'campuses' => $campuses,
            'isAdmin' => $isAdmin,
            'sorties' => $sorties,
            'selectedCampus' => $selectedCampus, // Transmettre le campus sélectionné au template
            'user' => $user, // Transmettre l'utilisateur connecté au template
        ]);
    }
}
