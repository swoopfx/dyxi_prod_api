<?php

declare(strict_types=1);

namespace Adhd;

use Laminas\Router\Http\Segment;
use Adhd\Controller\AdhdController;
use Adhd\Controller\Factory\AdhdControllerFactory;
use Adhd\Service\AdhdService;
use Adhd\Service\Factory\AdhdServiceFactory;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

return [
    'router' => [
        'routes' => [
            'api-adhd' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/api/adhd[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[a-zA-Z0-9_-]*'
                    ],
                    'defaults' => [
                        'controller' => AdhdController::class,
                        'action'     => 'register',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            AdhdController::class => AdhdControllerFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            AdhdService::class => AdhdServiceFactory::class,
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
