<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\Message;
use App\Enum\CardState;
use App\Enum\MessageState;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/moderation')]
#[IsGranted('ROLE_ADMIN')]
class AdminModerationController extends AbstractController
{
    #[Route('/card/{id}/approve', name: 'admin_moderate_card_approve', methods: ['POST'])]
    public function approveCard(Request $request, Card $card, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('approve_card' . $card->getId(), $request->request->get('_token'))) {
            // Rétablir la carte
            $card->setState(CardState::PUBLISHED);

            // Nettoyer les signalements
            foreach ($card->getReports() as $report) {
                $em->remove($report);
            }

            $em->flush();
            $this->addFlash('success', 'L\'annonce a été rétablie et les signalements supprimés.');
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('/card/{id}/archive', name: 'admin_moderate_card_archive', methods: ['POST'])]
    public function archiveCard(Request $request, Card $card, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('archive_card' . $card->getId(), $request->request->get('_token'))) {
            // Archiver la carte
            $card->setState(CardState::ARCHIVED);

            // Nettoyer les signalements
            foreach ($card->getReports() as $report) {
                $em->remove($report);
            }

            $em->flush();
            $this->addFlash('success', 'L\'annonce a été archivée.');
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('/message/{id}/approve', name: 'admin_moderate_message_approve', methods: ['POST'])]
    public function approveMessage(Request $request, Message $message, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('approve_message' . $message->getId(), $request->request->get('_token'))) {
            // Rétablir le message
            $message->setState(MessageState::PUBLISHED);

            // Nettoyer les signalements
            foreach ($message->getReports() as $report) {
                $em->remove($report);
            }

            $em->flush();
            $this->addFlash('success', 'Le message a été rétabli et les signalements supprimés.');
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('/message/{id}/archive', name: 'admin_moderate_message_archive', methods: ['POST'])]
    public function archiveMessage(Request $request, Message $message, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('archive_message' . $message->getId(), $request->request->get('_token'))) {
            // Archiver le message
            $message->setState(MessageState::ARCHIVED);
            // Nettoyer les signalements
            foreach ($message->getReports() as $report) {
                $em->remove($report);
            }

            $em->flush();
            $this->addFlash('success', 'Le message a été archivé.');
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }
}
