<?php

namespace Wallet;

use Wallet\Service\Factory\WalletApiServiceFactory;
use Wallet\Service\WalletApiService;

return [
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
    "view_manager" => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],

    "service_manager" => [
        "factories" => [
            WalletApiService::class => WalletApiServiceFactory::class
        ]
    ]

];
