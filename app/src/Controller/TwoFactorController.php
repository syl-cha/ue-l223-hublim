<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class TwoFactorController extends AbstractController
{
    #[Route('/2fa/choose', name: 'app_2fa_choose')]
    #[IsGranted('ROLE_USER')]
    public function choose(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isTwoFactorEnabled()) {
            return $this->redirectToRoute('app_card_index');
        }

        return $this->render('two_factor/choose.html.twig');
    }

    #[Route('/2fa/setup', name: 'app_2fa_setup')]
    #[IsGranted('ROLE_USER')]
    public function setup(
        Request $request,
        EntityManagerInterface $em,
        TotpAuthenticatorInterface $totpAuthenticator,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isTwoFactorEnabled()) {
            $this->addFlash('warning', 'La double authentification est déjà activée.');
            return $this->redirectToRoute('app_card_index');
        }

        $session = $request->getSession();
        $secret = $session->get('2fa_setup_secret');

        if (!$secret) {
            $secret = $totpAuthenticator->generateSecret();
            $session->set('2fa_setup_secret', $secret);
        }

        // Vérification du code soumis (avant la génération du QR)
        if ($request->isMethod('POST')) {
            $code = $request->request->getString('code');

            $user->setTwoFactorSecret($secret);
            $user->setIsTwoFactorEnabled(true);
            $user->setTwoFactorMethod('totp');

            if ($totpAuthenticator->checkCode($user, $code)) {
                $em->flush();
                $session->remove('2fa_setup_secret');

                $this->addFlash('success', 'Double authentification activée avec succès !');
                return $this->redirectToRoute('app_card_index');
            }

            // Code invalide : on remet l'état initial
            $user->setTwoFactorSecret(null);
            $user->setIsTwoFactorEnabled(false);

            $this->addFlash('error', 'Code invalide. Veuillez réessayer.');
        }

        // Générer le QR code
        $user->setTwoFactorSecret($secret);
        $user->setIsTwoFactorEnabled(true);

        $qrContent = $totpAuthenticator->getQRContent($user);

        $user->setTwoFactorSecret(null);
        $user->setIsTwoFactorEnabled(false);

        $qrCode = new QrCode(data: $qrContent, size: 250, margin: 10);
        $writer = new PngWriter();
        $qrCodeDataUri = $writer->write($qrCode)->getDataUri();

        return $this->render('two_factor/setup.html.twig', [
            'qrCodeDataUri' => $qrCodeDataUri,
            'secret' => $secret,
        ]);
    }

    #[Route('/2fa/setup-email', name: 'app_2fa_setup_email', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function setupEmail(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isTwoFactorEnabled()) {
            $this->addFlash('warning', 'La double authentification est déjà activée.');
            return $this->redirectToRoute('app_card_index');
        }

        $user->setTwoFactorSecret(null);
        $user->setTwoFactorMethod('email');
        $user->setIsTwoFactorEnabled(true);
        $em->flush();

        $this->addFlash('success', 'Double authentification par email activée avec succès !');
        return $this->redirectToRoute('app_card_index');
    }

    #[Route('/2fa/disable', name: 'app_2fa_disable')]
    #[IsGranted('ROLE_USER')]
    public function disable(
        Request $request,
        EntityManagerInterface $em,
        TotpAuthenticatorInterface $totpAuthenticator,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->isTwoFactorEnabled()) {
            return $this->redirectToRoute('app_card_index');
        }

        if ($request->isMethod('POST')) {
            $code = $request->request->getString('code');
            $isValid = false;

            if ($user->getTwoFactorMethod() === 'totp') {
                $isValid = $totpAuthenticator->checkCode($user, $code);
            } elseif ($user->getTwoFactorMethod() === 'email') {
                $isValid = $user->getEmailAuthCode() === $code;
            }

            if ($isValid) {
                $user->setIsTwoFactorEnabled(false);
                $user->setTwoFactorSecret(null);
                $user->setTwoFactorMethod('totp');
                $em->flush();

                $this->addFlash('success', 'Double authentification désactivée.');
                return $this->redirectToRoute('app_card_index');
            }

            $this->addFlash('error', 'Code invalide.');
        }

        return $this->render('two_factor/disable.html.twig', [
            'method' => $user->getTwoFactorMethod(),
        ]);
    }
}
