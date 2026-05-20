<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    private const DEFAULT_LOCALE = 'es';
    private const SUPPORTED_LOCALES = ['es', 'ca', 'en'];

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $locale = $request->query->get('_locale');

        if (is_string($locale) && in_array($locale, self::SUPPORTED_LOCALES, true)) {
            if ($request->hasSession()) {
                $request->getSession()->set('_locale', $locale);
            }
        } else {
            $sessionLocale = $request->hasSession() ? $request->getSession()->get('_locale') : null;
            $locale = $sessionLocale
                ?? $request->getPreferredLanguage(self::SUPPORTED_LOCALES)
                ?? self::DEFAULT_LOCALE;
        }

        $request->setLocale($locale);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20],
        ];
    }
}
