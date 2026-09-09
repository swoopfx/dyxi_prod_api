<?php

declare(strict_types=1);

namespace Admin\Controller\Factory;

use Admin\Controller\RbacAdminController;
use Authorization\Service\AuthorizationService;
use Doctrine\ORM\EntityManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class RbacAdminControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $em = $container->get(EntityManager::class);
        $authorizationService = $container->get(AuthorizationService::class);
        $apiAuthService = $container->get("api_authentication_service");
        $jwtIssuer = $container->get(\Authentication\Service\JWTIssuer::class);

        return new RbacAdminController($em, $authorizationService, $apiAuthService, $jwtIssuer);
    }
}
