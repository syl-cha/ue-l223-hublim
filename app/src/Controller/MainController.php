<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\CardRepository;
use App\Repository\MessageRepository;
use App\Enum\CardState;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MainController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    public function index(UserRepository $userRepo, CardRepository $cardRepo, MessageRepository $messageRepo): Response
    {
        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $qb = $cardRepo->createQueryBuilder('c');

        if ($isAdmin) {
            $qb->where('c.state != :draft')
                ->setParameter('draft', CardState::DRAFT);
        } elseif ($user) {
            $qb->where('c.state = :published AND (c.user = :user OR NOT EXISTS (SELECT 1 FROM App\Entity\Message m WHERE m.card = c AND m.state = :msg_flagged))')
                ->orWhere('c.state = :flagged AND c.user = :user')
                ->setParameter('published', CardState::PUBLISHED)
                ->setParameter('flagged', CardState::FLAGGED)
                ->setParameter('msg_flagged', \App\Enum\MessageState::FLAGGED)
                ->setParameter('user', $user);
        } else {
            $qb->where('c.state = :published AND NOT EXISTS (SELECT 1 FROM App\Entity\Message m WHERE m.card = c AND m.state = :msg_flagged)')
                ->setParameter('published', CardState::PUBLISHED)
                ->setParameter('msg_flagged', \App\Enum\MessageState::FLAGGED);
        }
        $cards = $qb->orderBy('c.createdAt', 'DESC')->getQuery()->getResult();

        return $this->render('main/index.html.twig', [
            'users' => $userRepo->findAll(),
            'cards' => $cards,
            'totalCards' => $cardRepo->count([]),
            'totalUsers' => $userRepo->count([]),
            'totalMessages' => $messageRepo->count([]),
        ]);
    }
}
