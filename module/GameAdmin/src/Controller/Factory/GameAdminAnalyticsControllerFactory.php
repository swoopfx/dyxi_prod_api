<?php

/**
 * ============================================================================
 * DYXI Platform - GameAdmin Subsystem
 * File: GameAdminAnalyticsControllerFactory.php
 * ============================================================================
 * Description: Factory class responsible for instantiating GameAdminAnalyticsController
 * and injecting required dependencies.
 * ============================================================================
 */

declare(strict_types=1);

namespace GameAdmin\Controller\Factory;

use Doctrine\ORM\EntityManager;
use GameAdmin\Controller\GameAdminAnalyticsController;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Factory for GameAdminAnalyticsController.
 */
class GameAdminAnalyticsControllerFactory implements FactoryInterface
{
    /**
     * Instantiates GameAdminAnalyticsController.
     *
     * @param ContainerInterface $container PSR container instance.
     * @param string $requestedName
     * @param array|null $options
     * @return GameAdminAnalyticsController
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): GameAdminAnalyticsController
    {
        $entityManager = $container->has(EntityManager::class)
            ? $container->get(EntityManager::class)
            : null;

        return new GameAdminAnalyticsController($entityManager);
    }
}
