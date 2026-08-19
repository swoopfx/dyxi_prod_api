<?php

declare(strict_types=1);

namespace Dyslexia;

use Laminas\Router\Http\Segment;
use Dyslexia\Controller\DyslexiaController;
use Dyslexia\Controller\Factory\DyslexiaControllerFactory;
use Dyslexia\Service\DyslexiaService;
use Dyslexia\Service\Factory\DyslexiaServiceFactory;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

return [
    'router' => [
        'routes' => [
            'api-dyslexia' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/api/dyslexia[/:interface[/:action[/:id]]]',
                    'constraints' => [
                        'interface' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[a-zA-Z0-9_-]*'
                    ],
                    'defaults' => [
                        'controller' => DyslexiaController::class,
                        'interface'  => 'api',
                        'action'     => 'register',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            DyslexiaController::class => DyslexiaControllerFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            DyslexiaService::class => DyslexiaServiceFactory::class,
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
