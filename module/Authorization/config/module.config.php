<?php
declare(strict_types=1);

namespace Authorization;

use Authorization\Listener\AuthorizationListener;
use Authorization\Listener\Factory\AuthorizationListenerFactory;
use Authorization\Service\AuthorizationService;
use Authorization\Service\Factory\AuthorizationServiceFactory;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

return [
    'service_manager' => [
        'factories' => [
            AuthorizationService::class => AuthorizationServiceFactory::class,
            AuthorizationListener::class => AuthorizationListenerFactory::class,
        ],
        'aliases' => [
            'authorization_service' => AuthorizationService::class,
        ],
    ],
    'doctrine' => [
        'driver' => [
            __NAMESPACE__ . '_driver' => [
                'class' => AnnotationDriver::class,
                'cache' => 'array',
                'paths' => [
                    __DIR__ . '/../src/Entity',
                ],
            ],
            'orm_default' => [
                'drivers' => [
                    __NAMESPACE__ . '\Entity' => __NAMESPACE__ . '_driver',
                ],
            ],
        ],
    ],
];
