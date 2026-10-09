<?php

/**
 * ============================================================================
 * DYXI Platform - GameAdmin Subsystem
 * File: GameAdminDashboardControllerFactory.php
 * ============================================================================
 * Description: Factory class responsible for instantiating GameAdminDashboardController
 * and injecting required dependencies.
 * ============================================================================
 */

declare(strict_types=1);

namespace GameAdmin\Controller\Factory;

use Doctrine\ORM\EntityManager;
use Game\Service\CurriculumService;
use GameAdmin\Controller\GameAdminDashboardController;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Factory for GameAdminDashboardController.
 */
class GameAdminDashboardControllerFactory implements FactoryInterface
{
    /**
     * Instantiates GameAdminDashboardController.
     *
     * @param ContainerInterface $container PSR container instance.
     * @param string $requestedName
     * @param array|null $options
     * @return GameAdminDashboardController
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): GameAdminDashboardController
    {
        $entityManager = $container->has(EntityManager::class)
            ? $container->get(EntityManager::class)
            : null;

        $curriculumService = $container->has(CurriculumService::class)
            ? $container->get(CurriculumService::class)
            : null;

        return new GameAdminDashboardController($entityManager, $curriculumService);
    }
}
