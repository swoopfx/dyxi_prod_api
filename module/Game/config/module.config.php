<?php

declare(strict_types=1);

namespace Game;

use Laminas\Router\Http\Segment;
use Game\Controller\GameTypeController;
use Game\Controller\CurriculumController;
use Game\Controller\GameController;
use Game\Controller\CollectionController;
use Game\Controller\Factory\GameTypeControllerFactory;
use Game\Controller\Factory\CurriculumControllerFactory;
use Game\Controller\Factory\GameControllerFactory;
use Game\Controller\Factory\CollectionControllerFactory;
use Game\Service\GameService;
use Game\Service\Factory\GameServiceFactory;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

return [
    'router' => [
        'routes' => [
            'api-game-type' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/api/game/game-type[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[a-zA-Z0-9_-]*'
                    ],
                    'defaults' => [
                        'controller' => GameTypeController::class,
                        'interface'  => 'api',
                        'action'     => 'list',
                    ],
                ],
            ],
            'api-curriculum' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/api/game/curriculum[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[a-zA-Z0-9_-]*'
                    ],
                    'defaults' => [
                        'controller' => CurriculumController::class,
                        'interface'  => 'api',
                        'action'     => 'list',
                    ],
                ],
            ],
            'api-game-entity' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/api/game/game[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[a-zA-Z0-9_-]*'
                    ],
                    'defaults' => [
                        'controller' => GameController::class,
                        'interface'  => 'api',
                        'action'     => 'list',
                    ],
                ],
            ],
            'api-collection' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/api/game/collection[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[a-zA-Z0-9_-]*'
                    ],
                    'defaults' => [
                        'controller' => CollectionController::class,
                        'interface'  => 'api',
                        'action'     => 'list',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            GameTypeController::class   => GameTypeControllerFactory::class,
            CurriculumController::class => CurriculumControllerFactory::class,
            GameController::class       => GameControllerFactory::class,
            CollectionController::class => CollectionControllerFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            GameService::class => GameServiceFactory::class,
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
