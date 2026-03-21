<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TwoFactorSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Ne pas bloquer ces routes
        $allowedRoutes = [
            'app_2fa_verify',
            'app_login',
            'app_logout',
            'app_register',
            'app_verify_email',
            '_profiler',
            '_wdt',
        ];

        if (in_array($route, $allowedRoutes, true)) {
            return;
        }

        // Routes commençant par _profiler ou _wdt (debug toolbar)
        if ($route && (str_starts_with($route, '_profiler') || str_starts_with($route, '_wdt'))) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        // Si 2FA activée mais pas encore vérifiée dans cette session
        if ($user->isTwoFactorEnabled()) {
            $session = $request->getSession();
            if ($session->get('2fa_pending_user_id') && !$session->get('2fa_verified')) {
                $event->setResponse(
                    new RedirectResponse($this->urlGenerator->generate('app_2fa_verify'))
                );
            }
        }
    }
}
