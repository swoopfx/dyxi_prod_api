<?php

namespace Ward\Controller\Factory;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Ward\Service\WardService;
use Authentication\Service\ApiAuthenticateService;
use Ward\Controller\WardController;

class WardControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $wardService = $container->get(WardService::class);
        $apiAuthService = $container->get(ApiAuthenticateService::class);
        return new WardController($wardService, $apiAuthService);
    }
}
