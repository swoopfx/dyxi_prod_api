<?php

declare(strict_types=1);

namespace Dyscalculia;

use Laminas\Router\Http\Segment;
use Dyscalculia\Controller\DyscalculiaController;
use Dyscalculia\Controller\Factory\DyscalculiaControllerFactory;
use Dyscalculia\Service\DyscalculiaService;
use Dyscalculia\Service\Factory\DyscalculiaServiceFactory;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

return [
    'router' => [
        'routes' => [
            'api-dyscalculia' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/api/dyscalculia[/:interface[/:action[/:id]]]',
                    'constraints' => [
                        'interface' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[a-zA-Z0-9_-]*'
                    ],
                    'defaults' => [
                        'controller' => DyscalculiaController::class,
                        'interface'  => 'api',
                        'action'     => 'register',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            DyscalculiaController::class => DyscalculiaControllerFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            DyscalculiaService::class => DyscalculiaServiceFactory::class,
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
