<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\Message;
use App\Form\MessageType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/message')]
final class MessageController extends AbstractController
{
    #[Route('/new/{id}', name: 'app_message_new', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, Card $card, EntityManagerInterface $entityManager): Response
    {
        $message = new Message();
        $message->setCard($card);
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $message->setUser($user);
        $message->setCreatedAt(new \DateTimeImmutable());
        $message->setIsRead(false);

        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($message);
            $entityManager->flush();

            $this->addFlash('success', 'Votre message a été ajouté.');
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }

        return $this->redirectToRoute('app_card_show', ['id' => $card->getId()]);
    }

    #[Route('/{id}/edit', name: 'app_message_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Message $message, EntityManagerInterface $entityManager): Response
    {
        if ($message->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce message.');
        }

        if ($message->isRead()) {
            $this->addFlash('danger', 'Ce message ne peut plus être modifié car il a été lu entre-temps.');
            return $this->redirectToRoute('app_card_show', ['id' => $message->getCard()->getId()]);
        }

        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Votre message a été modifié.');
            return $this->redirectToRoute('app_card_show', ['id' => $message->getCard()->getId()]);
        }

        return $this->render('message/edit.html.twig', [
            'message' => $message,
            'form' => $form->createView(),
        ]);
    }
}
