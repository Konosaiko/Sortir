<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    #[Route('/profil', name: 'app_profile')]
    public function index(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifie si un nouveau mot de passe a été fourni
            $newPassword = $form->get('plainPassword')->getData();
            if (!empty($newPassword)) {
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
            }

            // Gestion de l'upload de l'image de profil
            $profilePictureFile = $form->get('profilePicture')->getData();
            if ($profilePictureFile) {
                $originalFilename = pathinfo($profilePictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename.'-'.uniqid().'.'.$profilePictureFile->guessExtension();

                try {
                    $profilePictureFile->move(
                        $this->getParameter('profile_picture_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Gérer l'erreur
                }

                // Mettre à jour le chemin de l'image de profil dans l'entité utilisateur
                $user->setProfilePicture($newFilename);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/profil.html.twig', [
            'form' => $form->createView(),
            'profilePictureFilename' => $user->getProfilePicture(), // Passer le nom du fichier au template Twig
            'user' => $user,
        ]);
    }

    #[Route('/profil/{username}', name: 'app_user_profile')]
    public function userProfile(string $username, EntityManagerInterface $entityManager): Response
    {
        // Récupérer l'utilisateur connecté
        $currentUser = $this->getUser();

        // Si l'utilisateur connecté essaie d'accéder à son propre profil via /profile/{username},
        // redirigez-le vers /profile
        if ($currentUser instanceof User && $currentUser->getUsername() === $username) {
            return $this->redirectToRoute('app_profile');
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['username' => $username]);

        if (!$user) {
            throw $this->createNotFoundException('L\'utilisateur demandé n\'existe pas.');
        }

        // Récupérer le nom du fichier de la photo de profil
        $profilePictureFilename = $user->getProfilePicture();

        return $this->render('profile/user_profile.html.twig', [
            'user' => $user,
            'profilePictureFilename' => $profilePictureFilename, // Passer le nom du fichier au template Twig
        ]);
    }


}
