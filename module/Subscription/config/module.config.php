<?php

declare(strict_types=1);

namespace Subscription;

use Laminas\Router\Http\Segment;
use Laminas\Router\Http\Literal;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Subscription\Controller\SubscriptionController;
use Subscription\Controller\Factory\SubscriptionControllerFactory;
use Subscription\Service\TokenDecryptionService;
use Subscription\Service\Factory\TokenDecryptionServiceFactory;
use Subscription\Service\SubscriptionService;
use Subscription\Service\Factory\SubscriptionServiceFactory;

return [
    'router' => [
        'routes' => [
            'subscribe-web' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/subscribe[/:token]',
                    'constraints' => [
                        'token' => '[a-zA-Z0-9_=-]+',
                    ],
                    'defaults' => [
                        'controller' => SubscriptionController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'api-subscription' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/api/subscription[/:action]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        'controller' => SubscriptionController::class,
                        'action'     => 'types',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            SubscriptionController::class => SubscriptionControllerFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            TokenDecryptionService::class => TokenDecryptionServiceFactory::class,
            SubscriptionService::class    => SubscriptionServiceFactory::class,
        ],
    ],
    'view_manager' => [
        'template_map' => [
            'subscription/subscription/index' => __DIR__ . '/../view/subscription/subscription/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
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
