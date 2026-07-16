<?php

declare(strict_types=1);

namespace Resources;

use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\Mvc\Controller\LazyControllerAbstractFactory;

return [
    'router' => [
        'routes' => [
            'api-resources' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/api/resources/:controller[/:action[/:id]]',
                    'constraints' => [
                        'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'         => '[a-zA-Z0-9_-]*'
                    ],
                    'defaults' => [
                        'action' => 'index',
                    ],
                ],
            ],
            // More specific routes could be defined, but we'll define direct ones for clarity:
            'api-resources-professionals' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/resources/professionals[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[a-zA-Z0-9_-]*'
                    ],
                    'defaults' => [
                        'controller' => Controller\ProfessionalsController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'api-resources-booking' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/resources/booking[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[a-zA-Z0-9_-]*'
                    ],
                    'defaults' => [
                        'controller' => Controller\BookingController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'api-resources-billing' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/resources/billing[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[a-zA-Z0-9_-]*'
                    ],
                    'defaults' => [
                        'controller' => Controller\BillingController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'api-resources-settings' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/resources/settings[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[a-zA-Z0-9_-]*'
                    ],
                    'defaults' => [
                        'controller' => Controller\SettingsController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\ProfessionalsController::class => Controller\Factory\ProfessionalsControllerFactory::class,
            Controller\BookingController::class       => Controller\Factory\BookingControllerFactory::class,
            Controller\BillingController::class       => Controller\Factory\BillingControllerFactory::class,
            Controller\SettingsController::class      => Controller\Factory\SettingsControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
];
