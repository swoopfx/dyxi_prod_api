<?php

declare(strict_types=1);

namespace Consultant;

use Laminas\Router\Http\Segment;
use Consultant\Controller\ConsultantController;
use Consultant\Controller\Factory\ConsultantControllerFactory;
use Consultant\Service\ConsultantService;
use Consultant\Service\Factory\ConsultantServiceFactory;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

return [
    'router' => [
        'routes' => [
            'api-consultant' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/api/consultant[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[a-zA-Z0-9_-]*'
                    ],
                    'defaults' => [
                        'controller' => ConsultantController::class,
                        'interface'  => 'api',
                        'action'     => 'list',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            ConsultantController::class => ConsultantControllerFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            ConsultantService::class => ConsultantServiceFactory::class,
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
