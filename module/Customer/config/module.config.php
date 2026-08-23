<?php

namespace Customer;

use Customer\Controller\CustomerController;
use Customer\Controller\DorihostController;
use Customer\Controller\Factory\CustomerControllerFactory;
use Customer\Controller\Factory\DorihostControllerFactory;
use Customer\Controller\Factory\MyrequestControllerFactory;
use Customer\Controller\MyrequestController;
use Customer\InputFilter\DropOffInputFilter;
use Customer\InputFilter\DropOffInputFilterFactory;
use Customer\InputFilter\PickUpInputFilter;
use Customer\InputFilter\PickUpInputFilterFactory;
use Customer\InputFilter\RequestWasteInputFilter;
use Customer\InputFilter\RequestWasteInputFilterFactory;
use Customer\Service\CustomerService;
use Customer\Service\DoriHostService;
use Customer\Service\Factory\CustomerServiceFactory;
use Customer\Service\Factory\DoriHostServiceFactory;
use Laminas\Router\Http\Segment;

return [
    "controllers" => [
        "factories" => [
            CustomerController::class => CustomerControllerFactory::class,
            DorihostController::class => DorihostControllerFactory::class,
            MyrequestController::class => MyrequestControllerFactory::class,
        ]
    ],

    'router' => [
        'routes' => [
            'customer-api' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/customer[/:interface[/:action[/:id]]]',
                    'constraints' => [
                        'interface' => '[a-zA-Z]*',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[a-zA-Z0-9_-]*'
                    ],
                    'defaults' => [
                        'controller' => CustomerController::class,
                        "interface" => "web",
                        'action'     => 'index',
                    ],
                ],
            ],

            'dori-api' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/dori[/:interface[/:action[/:id]]]',
                    'constraints' => [
                        'interface' => '[a-zA-Z]*',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[a-zA-Z0-9]*'
                    ],
                    'defaults' => [
                        'controller' => DorihostController::class,
                        "interface" => "web",
                        'action'     => 'index',
                    ],
                ],
            ],

            'request-api' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/request[/:interface[/:action[/:id]]]',
                    'constraints' => [
                        'interface' => '[a-zA-Z]*',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[a-zA-Z0-9_-]*'
                    ],
                    'defaults' => [
                        'controller' => MyrequestController::class,
                        "interface" => "web",
                        'action'     => 'index',
                    ],
                ],
            ],
            // 'loginjson' => [
            //     'type'    => Literal::class,
            //     'options' => [
            //         'route'    => '/login',
            //         'defaults' => [
            //             'controller' => AuthenticateController::class,
            //             'action'     => 'login',
            //         ],
            //     ],
            // ],

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
    "view_manager" => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    "input_filters" => [
        "factories" => [
            RequestWasteInputFilter::class => RequestWasteInputFilterFactory::class,
            DropOffInputFilter::class => DropOffInputFilterFactory::class,
            PickUpInputFilter::class => PickUpInputFilterFactory::class,

        ]
    ],
    "service_manager" => [
        "factories" => [
            CustomerService::class => CustomerServiceFactory::class,
            DoriHostService::class => DoriHostServiceFactory::class,
        ]
    ],

];
