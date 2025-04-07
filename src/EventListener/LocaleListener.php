<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LocaleListener implements EventSubscriberInterface
{
    private $defaultLocale;

    public function __construct(string $defaultLocale = 'en')
    {
        $this->defaultLocale = $defaultLocale;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        // First check if _locale is in the query parameters
        if ($locale = $request->query->get('_locale')) {
            $request->getSession()->set('_locale', $locale);
            $request->setLocale($locale);
        }
        // Then try to get locale from _locale attribute (from route)
        else if ($locale = $request->attributes->get('_locale')) {
            $request->getSession()->set('_locale', $locale);
        } else {
            // Try to get locale from the session
            $locale = $request->getSession()->get('_locale', $this->defaultLocale);
            $request->setLocale($locale);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            // Must be registered before the default Locale listener
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
