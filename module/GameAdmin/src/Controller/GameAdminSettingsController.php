<?php

/**
 * ============================================================================
 * DYXI Platform - GameAdmin Subsystem
 * File: GameAdminSettingsController.php
 * ============================================================================
 * Description: Controller responsible for managing game engine runtime settings,
 * telemetry pipelines, and subsystem configuration options.
 * ============================================================================
 */

declare(strict_types=1);

namespace GameAdmin\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;

/**
 * Controller class for game engine settings and configuration options.
 */
class GameAdminSettingsController extends AbstractActionController
{
    /**
     * Renders Engine Runtime Settings View (/game-admin/settings).
     *
     * @return ViewModel
     */
    public function settingsAction(): ViewModel
    {
        $viewModel = new ViewModel([
            'activeNav'   => 'settings',
            'currentUser' => $this->getActiveUser(),
            'config'      => [
                'game_server_region'      => 'US-East (Virginia)',
                'max_concurrent_sessions' => 5000,
                'telemetry_enabled'       => true,
                'auto_save_interval'      => 30,
            ],
        ]);

        $viewModel->setTemplate('game-admin/game-admin/settings');
        return $viewModel;
    }

    /**
     * Retrieves current user session payload.
     *
     * @return array
     */
    private function getActiveUser(): array
    {
        $session = new Container('GameAdmin');
        return $session->user ?? [
            'username'  => 'Game Administrator',
            'email'     => 'admin@dyxi.internal',
            'role_name' => 'SuperAdmin',
        ];
    }
}
