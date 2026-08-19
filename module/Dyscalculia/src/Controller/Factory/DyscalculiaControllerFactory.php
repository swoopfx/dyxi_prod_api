<?php

namespace Dyscalculia\Controller\Factory;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Dyscalculia\Service\DyscalculiaService;
use Authentication\Service\ApiAuthenticateService;
use Dyscalculia\Controller\DyscalculiaController;

class DyscalculiaControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $dyscalculiaService = $container->get(DyscalculiaService::class);
        $apiAuthService = $container->get(ApiAuthenticateService::class);
        return new DyscalculiaController($dyscalculiaService, $apiAuthService);
    }
}
