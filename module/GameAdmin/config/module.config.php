<?php

/**
 * ============================================================================
 * DYXI Platform - GameAdmin Subsystem
 * File: module.config.php
 * ============================================================================
 * Description: Module configuration file defining HTTP routing definitions,
 * controller factory registrations, view manager template maps, and layout stacks
 * for the GameAdmin module.
 * ============================================================================
 */

declare(strict_types=1);

namespace GameAdmin;

use GameAdmin\Controller\GameAdminAnalyticsController;
use GameAdmin\Controller\GameAdminAuthController;
use GameAdmin\Controller\GameAdminDashboardController;
use GameAdmin\Controller\GameAdminSettingsController;
use GameAdmin\Controller\GameManageController;
use GameAdmin\Controller\Factory\GameAdminAnalyticsControllerFactory;
use GameAdmin\Controller\Factory\GameAdminAuthControllerFactory;
use GameAdmin\Controller\Factory\GameAdminDashboardControllerFactory;
use GameAdmin\Controller\Factory\GameAdminSettingsControllerFactory;
use GameAdmin\Controller\Factory\GameManageControllerFactory;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;

return [
    /**
     * HTTP Route Definitions for GameAdmin Subsystem
     */
    'router' => [
        'routes' => [
            // Authentication: Login Endpoint (/game-admin/login)
            'game-admin-login' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/game-admin/login',
                    'defaults' => [
                        'controller' => GameAdminAuthController::class,
                        'action'     => 'login',
                    ],
                ],
            ],
            // Authentication: Logout Endpoint (/game-admin/logout)
            'game-admin-logout' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/game-admin/logout',
                    'defaults' => [
                        'controller' => GameAdminAuthController::class,
                        'action'     => 'logout',
                    ],
                ],
            ],
            // Dashboard: Central Game Engine Command Center (/game-admin)
            'game-admin' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/game-admin',
                    'defaults' => [
                        'controller' => GameAdminDashboardController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            // Catalog: Game Catalog List View (/game-admin/games)
            'game-admin-games' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/game-admin/games',
                    'defaults' => [
                        'controller' => GameManageController::class,
                        'action'     => 'games',
                    ],
                ],
            ],
            // Catalog: Game Creation Form View (/game-admin/games/create)
            'game-admin-game-create' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/game-admin/games/create',
                    'defaults' => [
                        'controller' => GameManageController::class,
                        'action'     => 'createGame',
                    ],
                ],
            ],
            // Catalog: View Game Detail View (/game-admin/games/view/:id)
            'game-admin-game-view' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/game-admin/games/view/:id',
                    'constraints' => [
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => GameManageController::class,
                        'action'     => 'viewGame',
                    ],
                ],
            ],
            // Catalog: Edit Game Entity View (/game-admin/games/edit/:id)
            'game-admin-game-edit' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/game-admin/games/edit/:id',
                    'constraints' => [
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => GameManageController::class,
                        'action'     => 'editGame',
                    ],
                ],
            ],
            // Analytics: Telemetry & Performance Dashboard (/game-admin/analytics)
            'game-admin-analytics' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/game-admin/analytics',
                    'defaults' => [
                        'controller' => GameAdminAnalyticsController::class,
                        'action'     => 'analytics',
                    ],
                ],
            ],
            // Settings: Game Engine Runtime Options (/game-admin/settings)
            'game-admin-settings' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/game-admin/settings',
                    'defaults' => [
                        'controller' => GameAdminSettingsController::class,
                        'action'     => 'settings',
                    ],
                ],
            ],
            // API: Live Real-time Polling Metrics (/api/game-admin/stats)
            'api-game-admin-stats' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/api/game-admin/stats',
                    'defaults' => [
                        'controller' => GameAdminAnalyticsController::class,
                        'action'     => 'statsApi',
                    ],
                ],
            ],
        ],
    ],

    /**
     * Controller Dependency Injection Factories
     */
    'controllers' => [
        'factories' => [
            GameAdminAuthController::class      => GameAdminAuthControllerFactory::class,
            GameAdminDashboardController::class => GameAdminDashboardControllerFactory::class,
            GameManageController::class         => GameManageControllerFactory::class,
            GameAdminAnalyticsController::class => GameAdminAnalyticsControllerFactory::class,
            GameAdminSettingsController::class  => GameAdminSettingsControllerFactory::class,
        ],
    ],

    /**
     * View Manager Template Configurations & Layout Stacks
     */
    'view_manager' => [
        'template_map' => [
            'layout/game-admin' => __DIR__ . '/../view/layout/game-admin-layout.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
