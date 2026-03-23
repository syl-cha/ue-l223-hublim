<?php

namespace App\Mailer;

use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class TwoFactorMailer implements AuthCodeMailerInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig
    ) {}

    public function sendAuthCode(TwoFactorInterface $user): void
    {
        $authCode = $user->getEmailAuthCode();

        // Création de l'email sur-mesure
        $email = (new Email())
            ->from('nepasrepondre@hublim.bradype.fr') // L'adresse d'envoi automatique
            ->replyTo('contact@hublim.bradype.fr')    // L'adresse où arriveront les réponses
            ->to($user->getEmailAuthRecipient())
            ->subject('HubLim - Votre code de sécurité')
            ->html($this->twig->render('security/2fa_form_email.html.twig', [
                'auth_code' => $authCode // On passe le code au template Twig
            ]));
        // on utilise le service SMTP spécifique
        $email->getHeaders()->addTextHeader('X-Transport', 'noreply');

        $this->mailer->send($email);
    }
}
