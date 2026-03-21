<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use OTPHP\TOTP;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class TwoFactorController extends AbstractController
{
    #[Route('/2fa/setup', name: 'app_2fa_setup')]
    #[IsGranted('ROLE_USER')]
    public function setup(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isTwoFactorEnabled()) {
            $this->addFlash('warning', 'La double authentification est déjà activée.');
            return $this->redirectToRoute('app_card_index');
        }

        // Génère un secret TOTP et le stocke temporairement en session
        $session = $request->getSession();
        $secret = $session->get('2fa_setup_secret');

        if (!$secret) {
            $totp = TOTP::generate();
            $secret = $totp->getSecret();
            $session->set('2fa_setup_secret', $secret);
        }

        $totp = TOTP::create($secret);
        $totp->setLabel($user->getEmail());
        $totp->setIssuer('HubLim');

        // Génère le QR code
        $qrCodeUri = $totp->getProvisioningUri();
        $builder = new Builder();
        $result = $builder->build(data: $qrCodeUri, size: 250, margin: 10);

        $qrCodeDataUri = $result->getDataUri();

        // Vérification du code soumis
        if ($request->isMethod('POST')) {
            $code = $request->request->getString('code');

            if ($totp->verify($code, null, 1)) {
                $user->setTwoFactorSecret($secret);
                $user->setIsTwoFactorEnabled(true);
                $em->flush();

                $session->remove('2fa_setup_secret');

                $this->addFlash('success', 'Double authentification activée avec succès !');
                return $this->redirectToRoute('app_card_index');
            }

            $this->addFlash('error', 'Code invalide. Veuillez réessayer.');
        }

        return $this->render('two_factor/setup.html.twig', [
            'qrCodeDataUri' => $qrCodeDataUri,
            'secret' => $secret,
        ]);
    }

    #[Route('/2fa/disable', name: 'app_2fa_disable')]
    #[IsGranted('ROLE_USER')]
    public function disable(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->isTwoFactorEnabled()) {
            return $this->redirectToRoute('app_card_index');
        }

        if ($request->isMethod('POST')) {
            $code = $request->request->getString('code');
            $totp = TOTP::create($user->getTwoFactorSecret());

            if ($totp->verify($code, null, 1)) {
                $user->setIsTwoFactorEnabled(false);
                $user->setTwoFactorSecret(null);
                $em->flush();

                $this->addFlash('success', 'Double authentification désactivée.');
                return $this->redirectToRoute('app_card_index');
            }

            $this->addFlash('error', 'Code invalide.');
        }

        return $this->render('two_factor/disable.html.twig');
    }

    #[Route('/2fa/verify', name: 'app_2fa_verify')]
    public function verify(Request $request): Response
    {
        $session = $request->getSession();

        // Si pas de 2FA en attente, rediriger
        if (!$session->get('2fa_pending_user_id')) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $code = $request->request->getString('code');
            $secret = $session->get('2fa_pending_secret');

            $totp = TOTP::create($secret);

            if ($totp->verify($code, null, 1)) {
                // Marquer la 2FA comme validée
                $session->set('2fa_verified', true);
                $session->remove('2fa_pending_user_id');
                $session->remove('2fa_pending_secret');

                $targetPath = $session->get('2fa_target_path', $this->generateUrl('app_card_index'));
                $session->remove('2fa_target_path');

                return $this->redirect($targetPath);
            }

            $this->addFlash('error', 'Code invalide. Veuillez réessayer.');
        }

        return $this->render('two_factor/verify.html.twig');
    }
}
