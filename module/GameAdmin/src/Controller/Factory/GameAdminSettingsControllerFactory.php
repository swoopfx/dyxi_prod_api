<?php

/**
 * ============================================================================
 * DYXI Platform - GameAdmin Subsystem
 * File: GameAdminSettingsControllerFactory.php
 * ============================================================================
 * Description: Factory class responsible for instantiating GameAdminSettingsController.
 * ============================================================================
 */

declare(strict_types=1);

namespace GameAdmin\Controller\Factory;

use GameAdmin\Controller\GameAdminSettingsController;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Factory for GameAdminSettingsController.
 */
class GameAdminSettingsControllerFactory implements FactoryInterface
{
    /**
     * Instantiates GameAdminSettingsController.
     *
     * @param ContainerInterface $container PSR container instance.
     * @param string $requestedName
     * @param array|null $options
     * @return GameAdminSettingsController
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): GameAdminSettingsController
    {
        return new GameAdminSettingsController();
    }
}
