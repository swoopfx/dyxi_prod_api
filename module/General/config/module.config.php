<?php

declare(strict_types=1);

namespace General;

use General\Controller\Factory\GeneralControllerFactory;
use General\Controller\GeneralController;
use General\Service\Factory\GeneralServiceFactory;
use General\Service\Factory\ImageServiceFactory;
use General\Service\ImageService;
use General\Service\Mailtrap\Factory\MailtrapServiceFactory;
use General\Service\Mailtrap\MailtrapService;
use General\Service\Postmark\AuthenticationEmailService;
use General\Service\Postmark\Factory\AuthenticationEmailServiceFactory;
use General\Service\Pusher\Factory\PusherServiceFactory;
use General\Service\Pusher\PusherService;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'router' => [
        'routes' => [
            'general' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/general[/:interface[/:action[/:id]]]',
                    'constraints' => [
                        'interface' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[a-zA-Z0-9]*'
                    ],
                    'defaults' => [
                        'controller' => GeneralController::class,
                        "interface" => "api",
                        'action'     => 'index',
                    ],
                ],
            ],

        ],
    ],
    'controllers' => [
        'factories' => [
            GeneralController::class => GeneralControllerFactory::class
        ],
    ],

    'doctrine' => [
        'driver' => [
            __NAMESPACE__ . '_driver' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
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
    ],
    "service_manager" => [
        "factories" => [
            "General\Service\GeneralService" => GeneralServiceFactory::class,
            MailtrapService::class => MailtrapServiceFactory::class,

            // Pusher Settings
            PusherService::class => PusherServiceFactory::class,

            // Email Service
            AuthenticationEmailService::class => AuthenticationEmailServiceFactory::class,

            ImageService::class => ImageServiceFactory::class,
        ],
        "aliases" => [
            "general_service" => "General\Service\GeneralService",
            "postmark_email_authentication_service" => AuthenticationEmailService::class,
            "mailtrap_service" => MailtrapService::class,
        ]
    ],
    'view_manager' => [

        'template_map' => [],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
