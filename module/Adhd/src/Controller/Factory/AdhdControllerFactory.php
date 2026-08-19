<?php

namespace Adhd\Controller\Factory;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Adhd\Service\AdhdService;
use Authentication\Service\ApiAuthenticateService;
use Adhd\Controller\AdhdController;

class AdhdControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $adhdService = $container->get(AdhdService::class);
        $apiAuthService = $container->get(ApiAuthenticateService::class);
        return new AdhdController($adhdService, $apiAuthService);
    }
}
