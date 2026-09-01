<?php
declare(strict_types=1);

namespace Authorization;

use Authorization\Listener\AuthorizationListener;
use Laminas\Mvc\MvcEvent;

class Module
{
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event): void
    {
        $application = $event->getApplication();
        $eventManager = $application->getEventManager();
        $serviceManager = $application->getServiceManager();

        if ($serviceManager->has(AuthorizationListener::class)) {
            /** @var AuthorizationListener $listener */
            $listener = $serviceManager->get(AuthorizationListener::class);
            $eventManager->attach(MvcEvent::EVENT_ROUTE, $listener, -100);
        }
    }
}
