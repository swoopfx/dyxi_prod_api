<?php

declare(strict_types=1);

namespace Ward;

use Laminas\Router\Http\Segment;
use Ward\Controller\WardController;
use Ward\Controller\Factory\WardControllerFactory;
use Ward\Service\WardService;
use Ward\Service\Factory\WardServiceFactory;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

return [
    'router' => [
        'routes' => [
            'api-ward' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/api/ward[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[a-zA-Z0-9_-]*'
                    ],
                    'defaults' => [
                        'controller' => WardController::class,
                        'action'     => 'register',
                        'interface'  => 'api',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            WardController::class => WardControllerFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            WardService::class => WardServiceFactory::class,
        ],
    ],
    'view_manager' => [
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
    'doctrine' => [
        'driver' => [
            __NAMESPACE__ . '_driver' => [
                'class' => AnnotationDriver::class,
                'cache' => 'array',
                'paths' => [
                    __DIR__ . '/../src/Entity'
                ]
            ],
            'orm_default' => [
                'drivers' => [
                    __NAMESPACE__ . '\Entity' => __NAMESPACE__ . '_driver'
                ]
            ]
        ]
    ]
];
