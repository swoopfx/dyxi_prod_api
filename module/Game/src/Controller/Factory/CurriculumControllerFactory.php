<?php

namespace Game\Controller\Factory;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Game\Service\CurriculumService;
use Authentication\Service\ApiAuthenticateService;
use Game\Controller\CurriculumController;

class CurriculumControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $curriculumService = $container->get(CurriculumService::class);
        $apiAuthService = $container->get(ApiAuthenticateService::class);
        return new CurriculumController($curriculumService, $apiAuthService);
    }
}
