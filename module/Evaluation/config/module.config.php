<?php

declare(strict_types=1);

namespace Evaluation;

use Laminas\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'api-evaluation' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/evaluation/:controller[/:action[/:id]]',
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
            'api-evaluation-dyslexiasubtype' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/evaluation/dyslexia-subtype[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[a-zA-Z0-9_-]*'
                    ],
                    'defaults' => [
                        'controller' => Controller\DyslexiaSubtypeController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'api-evaluation-kaufmandyslexia' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/evaluation/kaufman-dyslexia[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[a-zA-Z0-9_-]*'
                    ],
                    'defaults' => [
                        'controller' => Controller\KaufmanDyslexiaController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'api-evaluation-adhd' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/evaluation/adhd[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[a-zA-Z0-9_-]*'
                    ],
                    'defaults' => [
                        'controller' => Controller\ADHDController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'api-evaluation-dyscalculia' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/evaluation/dyscalculia[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[a-zA-Z0-9_-]*'
                    ],
                    'defaults' => [
                        'controller' => Controller\DysCalculiaController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'api-evaluation-wiat4' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/evaluation/wiat4[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[a-zA-Z0-9_-]*'
                    ],
                    'defaults' => [
                        'controller' => Controller\Wiat4Controller::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'api-evaluation-ctoop2' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/evaluation/ctoop2[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[a-zA-Z0-9_-]*'
                    ],
                    'defaults' => [
                        'controller' => Controller\Ctoop2Controller::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'api-evaluation-neurodiversityscale' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/evaluation/neuro-diversity-scale[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[a-zA-Z0-9_-]*'
                    ],
                    'defaults' => [
                        'controller' => Controller\NeuroDiversityScaleController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\DyslexiaSubtypeController::class     => Controller\Factory\DyslexiaSubtypeControllerFactory::class,
            Controller\KaufmanDyslexiaController::class     => Controller\Factory\KaufmanDyslexiaControllerFactory::class,
            Controller\ADHDController::class                => Controller\Factory\ADHDControllerFactory::class,
            Controller\DysCalculiaController::class         => Controller\Factory\DysCalculiaControllerFactory::class,
            Controller\Wiat4Controller::class               => Controller\Factory\Wiat4ControllerFactory::class,
            Controller\Ctoop2Controller::class              => Controller\Factory\Ctoop2ControllerFactory::class,
            Controller\NeuroDiversityScaleController::class => Controller\Factory\NeuroDiversityScaleControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
];
