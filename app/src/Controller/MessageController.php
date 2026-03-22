<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\Message;
use App\Form\MessageType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur gérant les messages liés aux annonces (cartes).
 */
#[Route('/message')]
final class MessageController extends AbstractController
{
    /**
     * Crée et enregistre un nouveau message pour une annonce spécifique.
     *
     * @param Request                $request       La requête HTTP contenant les données du formulaire.
     * @param Card                   $card          L'annonce (carte) à laquelle le message est rattaché.
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités pour sauvegarder le message.
     * @param MailerInterface        $mailer        Service d'envoi d'emails.
     *
     * @return Response La réponse HTTP redirigeant vers la page de l'annonce.
     */
    #[Route('/new/{id}', name: 'app_message_new', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, Card $card, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
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

            if ($card->getUser() !== $user) {
                $email = (new TemplatedEmail())
                    ->from(new Address('notifications@hublim.bradype.fr', 'HubLim Notifications'))
                    ->to($card->getUser()->getEmail())
                    ->subject('Nouveau message sur votre annonce : ' . $card->getTitle())
                    ->htmlTemplate('emails/notification_new_message.html.twig')
                    ->context([
                        'card' => $card,
                        'message' => $message,
                    ]);

                $email->getHeaders()->addTextHeader('X-Transport', 'notifications');
                $mailer->send($email);

                $this->addFlash('info', 'Un email de notification vient d\'être envoyé à l\'auteur de la carte.');
            }
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }

        return $this->redirectToRoute('app_card_show', ['id' => $card->getId()]);
    }

    /**
     * Affiche et traite le formulaire de modification d'un message existant.
     *
     * Un message ne peut être modifié que par son auteur et s'il n'a pas encore été lu.
     *
     * @param Request                $request       La requête HTTP contenant les données du formulaire.
     * @param Message                $message       Le message à modifier.
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités pour sauvegarder les modifications.
     * @param MailerInterface        $mailer        Service d'envoi d'emails.
     *
     * @return Response La réponse HTTP (rendu du formulaire d'édition ou redirection).
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException Si l'utilisateur n'est pas l'auteur du message.
     */
    #[Route('/{id}/edit', name: 'app_message_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Message $message, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
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

            $card = $message->getCard();
            /** @var \App\Entity\User $user */
            $user = $this->getUser();

            if ($card->getUser() !== $user) {
                $email = (new TemplatedEmail())
                    ->from(new Address('notifications@hublim.bradype.fr', 'HubLim Notifications'))
                    ->to($card->getUser()->getEmail())
                    ->subject('Message modifié sur votre annonce : ' . $card->getTitle())
                    ->htmlTemplate('emails/notification_edit_message.html.twig')
                    ->context([
                        'card' => $card,
                        'message' => $message,
                    ]);

                $email->getHeaders()->addTextHeader('X-Transport', 'notifications');
                $mailer->send($email);

                $this->addFlash('info', 'Un email de notification vient d\'être envoyé à l\'auteur de la carte.');
            }

            return $this->redirectToRoute('app_card_show', ['id' => $message->getCard()->getId()]);
        }

        return $this->render('message/edit.html.twig', [
            'message' => $message,
            'form' => $form->createView(),
        ]);
    }
}
