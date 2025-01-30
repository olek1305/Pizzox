<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class LocaleListener implements EventSubscriberInterface
{
    public function __construct(
        private string $defaultLocale = 'en'
    ) {}

    /**
     * @param RequestEvent $event
     * @return void
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $session = $request->getSession();

        if ($locale = $request->query->get('_locale')) {
            $request->attributes->set('_locale', $locale);
        }

        if ($locale = $request->attributes->get('_locale')) {
            $session->set('_locale', $locale);
        } else {
            $locale = $session->get('_locale', $this->defaultLocale);
            $request->setLocale($locale);
        }
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
