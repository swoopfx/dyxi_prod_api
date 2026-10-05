<?php

/**
 * ============================================================================
 * DYXI Platform - GameAdmin Subsystem
 * File: GameAdminAuthControllerFactory.php
 * ============================================================================
 * Description: Factory class responsible for instantiating GameAdminAuthController
 * and injecting required dependencies (Doctrine EntityManager).
 * ============================================================================
 */

declare(strict_types=1);

namespace GameAdmin\Controller\Factory;

use Doctrine\ORM\EntityManager;
use GameAdmin\Controller\GameAdminAuthController;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Factory for GameAdminAuthController.
 */
class GameAdminAuthControllerFactory implements FactoryInterface
{
    /**
     * Instantiates GameAdminAuthController with dependencies.
     *
     * @param ContainerInterface $container PSR container instance.
     * @param string $requestedName Requested service name.
     * @param array|null $options Service instantiation options.
     * @return GameAdminAuthController
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): GameAdminAuthController
    {
        $entityManager = $container->has(EntityManager::class)
            ? $container->get(EntityManager::class)
            : null;

        return new GameAdminAuthController($entityManager);
    }
}
