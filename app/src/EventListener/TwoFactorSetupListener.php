<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsEventListener(event: 'kernel.request', priority: -10)]
class TwoFactorSetupListener
{
    public function __construct(
        private Security $security,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        // Ne pas bloquer si l'utilisateur a déjà activé la 2FA
        if ($user->isTwoFactorEnabled()) {
            return;
        }

        // Routes autorisées sans 2FA (setup, logout, assets)
        $route = $event->getRequest()->attributes->get('_route');
        $allowedRoutes = ['app_2fa_choose', 'app_2fa_setup', 'app_2fa_setup_email', 'app_logout', '_wdt', '_profiler'];

        if (in_array($route, $allowedRoutes, true)) {
            return;
        }

        // Rediriger vers la page de choix 2FA
        $event->setResponse(
            new RedirectResponse($this->urlGenerator->generate('app_2fa_choose'))
        );
    }
}
