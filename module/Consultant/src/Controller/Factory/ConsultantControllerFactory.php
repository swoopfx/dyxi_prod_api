<?php

namespace Consultant\Controller\Factory;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Consultant\Service\ConsultantService;
use Authentication\Service\ApiAuthenticateService;
use Consultant\Controller\ConsultantController;

class ConsultantControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $consultantService = $container->get(ConsultantService::class);
        $apiAuthService = $container->get(ApiAuthenticateService::class);
        return new ConsultantController($consultantService, $apiAuthService);
    }
}
