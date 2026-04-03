<?php

namespace App\Controller;

use Knp\Component\Pager\PaginatorInterface;
use App\Repository\CardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Form\UserProfileType;
use App\Repository\ReportRepository;

final class AccountController extends AbstractController
{
    #[Route('/account/annonces', name: 'app_mes_annonces')]
    public function mesAnnonces(CardRepository $cardRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $cardRepository->findBy(
            ['user' => $this->getUser()],
            ['createdAt' => 'DESC']
        );

        $cards = $paginator->paginate($query, $request->query->getInt('page', 1), 10);

        return $this->render('account/annonces.html.twig', [
            'cards' => $cards,
        ]);
    }

    #[Route('/account/favoris', name: 'app_mes_favoris')]
    public function mesFavoris(PaginatorInterface $paginator, Request $request): Response
    {
        $user = $this->getUser();

        $query = $user->getUserFavoriteCards();

        $cards = $paginator->paginate($query, $request->query->getInt('page', 1), 10);

        return $this->render('account/favoris.html.twig', [
            'cards' => $cards,
        ]);
    }

    #[Route('/account/profil', name: 'app_mon_profil')]
    public function profil(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $avatarFile = $form->get('avatarFile')->getData();
            if ($avatarFile instanceof UploadedFile) {
                $newFilename = uniqid() . '.' . $avatarFile->guessExtension();
                $avatarFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/avatars',
                    $newFilename
                );
                // Supprimer l'ancienne photo
                $old = $user->getAvatarFileName();
                if ($old) {
                    $oldPath = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars/' . $old;
                    if (file_exists($oldPath)) unlink($oldPath);
                }
                $user->setAvatarFileName($newFilename);
            }

            $password = $form->get('password')->getData();
            if ($password) {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
            }

            $entityManager->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès.');

            return $this->redirectToRoute('app_mon_profil');
        }

        return $this->render('account/profil.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/account/profil/delete-avatar', name: 'app_profile_delete_avatar', methods: ['POST'])]
    public function deleteAvatar(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete_avatar', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_mon_profil');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $avatar = $user->getAvatarFileName();
        if ($avatar) {
            $path = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars/' . $avatar;
            if (file_exists($path)) {
                unlink($path);
            }
            $user->setAvatarFileName(null);
            $entityManager->flush();
            $this->addFlash('success', 'Photo de profil supprimée.');
        }

        return $this->redirectToRoute('app_mon_profil');
    }

    #[Route('/account/delete', name: 'app_account_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteAccount(
        Request $request,
        EntityManagerInterface $entityManager,
        ReportRepository $reportRepository,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_account', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_mon_profil');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $projectDir = $this->getParameter('kernel.project_dir');

        // Supprimer les reports de l'utilisateur
        foreach ($reportRepository->findBy(['reporter' => $user]) as $report) {
            $entityManager->remove($report);
        }

        // Supprimer les fichiers images des cards de l'utilisateur
        foreach ($user->getCard() as $card) {
            foreach ($card->getImages() as $image) {
                $basePath = $projectDir . '/public/uploads/cards/' . $image->getFileName();
                foreach (['-thumb.webp', '-medium.webp', '-full.webp', ''] as $suffix) {
                    $filePath = $basePath . $suffix;
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }
        }

        // Supprimer l'image de profil
        $avatar = $user->getAvatarFileName();
        if ($avatar) {
            $avatarPath = $projectDir . '/public/uploads/avatars/' . $avatar;
            if (file_exists($avatarPath)) {
                unlink($avatarPath);
            }
        }

        // Vider les favoris (table de jointure ManyToMany)
        $user->getUserFavoriteCards()->clear();

        // Supprimer l'utilisateur de la BDD (grâce à cascade: remove)
        $entityManager->remove($user);
        $entityManager->flush();

        // Invalider la session PHP et supprime le token de sécurité ce qui déconnecte l'utilisateur
        $request->getSession()->invalidate();
        $this->container->get('security.token_storage')->setToken(null);

        return $this->redirectToRoute('app_main');
    }

}
