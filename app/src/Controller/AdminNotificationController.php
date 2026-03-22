<?php

namespace App\Controller;

use App\Repository\CardRepository;
use App\Repository\MessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class AdminNotificationController extends AbstractController
{
    public function badge(CardRepository $cardRepository, MessageRepository $messageRepository): Response
    {
        $total = 0;

        // On ne calcule le total que pour les administrateurs pour économiser des requêtes
        if ($this->isGranted('ROLE_ADMIN')) {
            $total = $cardRepository->countFlaggedCards() + $messageRepository->countFlaggedMessages();
        }

        return $this->render('admin/_badge.html.twig', [
            'total' => $total
        ]);
    }
}
