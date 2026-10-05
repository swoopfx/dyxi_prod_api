<?php

/**
 * ============================================================================
 * DYXI Platform - GameAdmin Subsystem
 * File: GameManageControllerFactory.php
 * ============================================================================
 * Description: Factory class responsible for instantiating GameManageController
 * and injecting required dependencies.
 * ============================================================================
 */

declare(strict_types=1);

namespace GameAdmin\Controller\Factory;

use Doctrine\ORM\EntityManager;
use GameAdmin\Controller\GameManageController;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Factory for GameManageController.
 */
class GameManageControllerFactory implements FactoryInterface
{
    /**
     * Instantiates GameManageController.
     *
     * @param ContainerInterface $container PSR container instance.
     * @param string $requestedName
     * @param array|null $options
     * @return GameManageController
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): GameManageController
    {
        $entityManager = $container->has(EntityManager::class)
            ? $container->get(EntityManager::class)
            : null;

        return new GameManageController($entityManager);
    }
}
