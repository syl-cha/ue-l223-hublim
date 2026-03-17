<?php

namespace App\Controller;

use App\Repository\CardRepository;
use App\Repository\UserRepository;
use App\Entity\Card;
use App\Entity\User;
use App\Service\ImageUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('', name: 'app_admin_dashboard')]
    public function dashboard(
        Request $request,
        CardRepository $cardRepository,
        UserRepository $userRepository,
        PaginatorInterface $paginator
    ): Response {
        $searchCards = $request->query->get('search_cards', '');
        $searchUsers = $request->query->get('search_users', '');

        // Query annonces avec recherche
        $cardsQb = $cardRepository->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')
            ->orderBy('c.createdAt', 'DESC');

        if (!empty(trim($searchCards))) {
            $cardsQb->andWhere('c.title LIKE :searchCards OR c.description LIKE :searchCards OR u.firstName LIKE :searchCards OR u.lastName LIKE :searchCards')
                ->setParameter('searchCards', '%' . $searchCards . '%');
        }

        $cardsPagination = $paginator->paginate(
            $cardsQb->getQuery(),
            $request->query->getInt('page_cards', 1),
            10,
            ['pageParameterName' => 'page_cards']
        );

        // Query utilisateurs avec recherche
        $usersQb = $userRepository->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC');

        if (!empty(trim($searchUsers))) {
            $usersQb->andWhere('u.firstName LIKE :searchUsers OR u.lastName LIKE :searchUsers OR u.email LIKE :searchUsers')
                ->setParameter('searchUsers', '%' . $searchUsers . '%');
        }

        $usersPagination = $paginator->paginate(
            $usersQb->getQuery(),
            $request->query->getInt('page_users', 1),
            10,
            ['pageParameterName' => 'page_users']
        );

        return $this->render('admin/dashboard.html.twig', [
            'cards' => $cardsPagination,
            'users' => $usersPagination,
            'totalCards' => $cardRepository->count([]),
            'totalUsers' => $userRepository->count([]),
            'searchCards' => $searchCards,
            'searchUsers' => $searchUsers,
        ]);
    }

    #[Route('/card/{id}/delete', name: 'app_admin_card_delete', methods: ['POST'])]
    public function deleteCard(
        Request $request,
        Card $card,
        EntityManagerInterface $entityManager,
        ImageUploadService $imageUploadService
    ): Response {
        if ($this->isCsrfTokenValid('admin_delete' . $card->getId(), $request->getPayload()->getString('_token'))) {
            foreach ($card->getImages() as $image) {
                $imageUploadService->delete($image->getFileName());
            }
            $entityManager->remove($card);
            $entityManager->flush();
            $this->addFlash('success', 'Annonce supprimée par l\'administrateur.');
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('/user/{id}/toggle-admin', name: 'app_admin_toggle_role', methods: ['POST'])]
    public function toggleAdmin(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('toggle_admin' . $user->getId(), $request->getPayload()->getString('_token'))) {
            $roles = $user->getRoles();
            if (in_array('ROLE_ADMIN', $roles)) {
                $user->setRoles(array_diff($roles, ['ROLE_ADMIN']));
                $this->addFlash('success', $user->getFirstName() . ' n\'est plus administrateur.');
            } else {
                $user->setRoles(array_merge($roles, ['ROLE_ADMIN']));
                $this->addFlash('success', $user->getFirstName() . ' est maintenant administrateur.');
            }
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }
}
