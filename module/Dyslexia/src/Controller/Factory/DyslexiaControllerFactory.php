<?php

namespace Dyslexia\Controller\Factory;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Dyslexia\Service\DyslexiaService;
use Authentication\Service\ApiAuthenticateService;
use Dyslexia\Controller\DyslexiaController;

class DyslexiaControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $dyslexiaService = $container->get(DyslexiaService::class);
        $apiAuthService = $container->get(ApiAuthenticateService::class);
        return new DyslexiaController($dyslexiaService, $apiAuthService);
    }
}
