<?php

declare(strict_types=1);

namespace Admin;

use Admin\Controller\AdminController;
use Admin\Controller\Factory\AdminControllerFactory;
use Admin\Controller\AdminAuthController;
use Admin\Controller\Factory\AdminAuthControllerFactory;
use Admin\Controller\RbacAdminController;
use Admin\Controller\Factory\RbacAdminControllerFactory;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'admin-login' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/admin/login',
                    'defaults' => [
                        'controller' => AdminAuthController::class,
                        'action' => 'login',
                    ],
                ],
            ],
            'admin-logout' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/admin/logout',
                    'defaults' => [
                        'controller' => AdminAuthController::class,
                        'action' => 'logout',
                    ],
                ],
            ],
            'api-admin-auth-login' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/api/admin/auth/login',
                    'defaults' => [
                        'controller' => AdminAuthController::class,
                        'action' => 'authenticate',
                        'interface' => 'api',
                    ],
                ],
            ],
            'admin' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/admin',
                    'defaults' => [
                        'controller' => AdminController::class,
                        'action' => 'index',
                    ],
                ],
            ],
            'admin-rbac' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/admin/rbac',
                    'defaults' => [
                        'controller' => RbacAdminController::class,
                        'action' => 'index',
                    ],
                ],
            ],
            'api-admin-overview' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/api/admin/overview',
                    'defaults' => [
                        'controller' => AdminController::class,
                        'action' => 'systemOverview',
                        'interface' => 'api',
                    ],
                ],
            ],
            'api-admin-rbac' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/admin/rbac[/:action]',
                    'defaults' => [
                        'controller' => RbacAdminController::class,
                        'action' => 'dashboardData',
                        'interface' => 'api',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            AdminController::class => AdminControllerFactory::class,
            AdminAuthController::class => AdminAuthControllerFactory::class,
            RbacAdminController::class => RbacAdminControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
