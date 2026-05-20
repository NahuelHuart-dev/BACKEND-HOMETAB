<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TwoFactorAccessSubscriber implements EventSubscriberInterface
{
    private const ALLOWED_ROUTES = [
        'app_2fa_web_verify',
        'app_2fa_web_verify_submit',
        'app_logout',
    ];

    public function __construct(
        private Security $security,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 8],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = (string) $request->attributes->get('_route', '');
        
        // Ignoramos las rutas de la API. La API es stateless y maneja su propio 2FA via JWT.
        if (str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        if (in_array($route, self::ALLOWED_ROUTES, true) || str_starts_with($route, '_')) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || !$user->isTwoFactorEnabled()) {
            return;
        }

        if ($request->getSession()->get('two_factor_verified') === true) {
            return;
        }

        $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_2fa_web_verify')));
    }
}
