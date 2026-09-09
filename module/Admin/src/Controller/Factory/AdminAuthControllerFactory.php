<?php

declare(strict_types=1);

namespace Admin\Controller\Factory;

use Admin\Controller\AdminAuthController;
use Authentication\Service\JWTIssuer;
use Authorization\Service\AuthorizationService;
use Doctrine\ORM\EntityManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AdminAuthControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $em = $container->get(EntityManager::class);
        $apiAuthService = $container->get("api_authentication_service");
        $jwtIssuer = $container->get(JWTIssuer::class);
        $authorizationService = $container->get(AuthorizationService::class);

        return new AdminAuthController($em, $apiAuthService, $jwtIssuer, $authorizationService);
    }
}
