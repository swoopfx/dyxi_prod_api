<?php
declare(strict_types=1);

namespace Authorization\Listener\Factory;

use Authorization\Listener\AuthorizationListener;
use Authorization\Service\AuthorizationService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AuthorizationListenerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): AuthorizationListener
    {
        $authorizationService = $container->get(AuthorizationService::class);
        return new AuthorizationListener($authorizationService);
    }
}
