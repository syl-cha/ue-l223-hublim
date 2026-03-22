<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\Message;
use App\Entity\Report;
use App\Form\ReportType;
use App\Enum\CardState;
use App\Enum\MessageState;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/report')]
class ReportController extends AbstractController
{
    #[Route('/card/{id}', name: 'app_report_card', methods: ['POST'])]
    public function reportCard(Request $request, Card $card, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $report = new Report();
        $form = $this->createForm(ReportType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $report->setReporter($this->getUser());
            $report->setCard($card);

            // S'assurer de la date s'il n'y a pas de constructeur qui s'en charge
            if (method_exists($report, 'setCreateAt')) {
                $report->setCreateAt(new \DateTimeImmutable());
            }

            // Modifier le statut de la carte
            $card->setState(CardState::FLAGGED);

            $em->persist($report);
            $em->flush();

            $this->sendReportEmails($mailer, $report, 'Carte', $card->getUser()->getEmail(), $card->getTitle());

            $this->addFlash('success', 'Votre signalement concernant cette annonce a bien été pris en compte.');
        }

        return $this->redirectToRoute('app_card_show', ['id' => $card->getId()]);
    }

    #[Route('/message/{id}', name: 'app_report_message', methods: ['POST'])]
    public function reportMessage(Request $request, Message $message, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $report = new Report();
        $form = $this->createForm(ReportType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $report->setReporter($this->getUser());
            $report->setMessage($message);

            if (method_exists($report, 'setCreateAt')) {
                $report->setCreateAt(new \DateTimeImmutable());
            }

            // Modifier le statut du message
            $message->setState(MessageState::FLAGGED);

            $em->persist($report);
            $em->flush();

            $this->sendReportEmails($mailer, $report, 'Message', $message->getUser()->getEmail(), mb_substr($message->getContent(), 0, 50) . '...');

            $this->addFlash('success', 'Votre signalement concernant ce message a bien été pris en compte.');
        }

        $cardId = $message->getCard() ? $message->getCard()->getId() : null;
        if ($cardId) {
            return $this->redirectToRoute('app_card_show', ['id' => $cardId]);
        }

        return $this->redirectToRoute('app_card_index');
    }

    private function sendReportEmails(MailerInterface $mailer, Report $report, string $contentType, string $creatorEmail, string $contentPreview): void
    {
        // Email à l'Administrateur
        $adminEmail = (new Email())
            ->from('signalement@hublim.bradype.fr')
            ->to('admin@hublim.bradype.fr')
            ->subject("Nouveau signalement ($contentType)")
            ->text(sprintf(
                "L'utilisateur %s %s a signalé un(e) %s.\n\nContenu concerné : %s\n\nMotif : %s\n\nDate : %s",
                $report->getReporter()->getFirstName(),
                $report->getReporter()->getLastName(),
                $contentType,
                $contentPreview,
                $report->getReason(),
                (new \DateTimeImmutable())->format('d/m/Y H:i')
            ));

        // Email au Créateur du contenu
        $creatorEmailObj = (new Email())
            ->from('signalement@hublim.bradype.fr')
            ->to($creatorEmail)
            ->subject("Votre contenu a été signalé")
            ->text(sprintf(
                "Bonjour,\n\nNous vous informons qu'un problème a été détecté concernant votre %s.\nCelui-ci a été temporairement mis en quarantaine pour vérification.\n\nUn administrateur traitera le dossier prochainement.\n\nL'équipe HubLim",
                strtolower($contentType)
            ));

        $mailer->send($adminEmail);
        $mailer->send($creatorEmailObj);
    }
}
