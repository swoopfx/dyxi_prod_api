<?php

declare(strict_types=1);

namespace Authentication;

use Authentication\Controller\ApiauthenticateController;
use Authentication\Controller\AuthenticateController;
use Authentication\Controller\Factory\ApiauthenticateControllerFactory;
use Authentication\Controller\Factory\AuthenticateControllerFactory;
use Authentication\Entity\User;
use Authentication\Form\Fieldset\UserFieldset;
use Authentication\Form\Fieldset\Factory\UserFieldsetFactory;
use Authentication\Form\InputFilter\Factory\RegisterInputfilterFactory;
use Authentication\Form\InputFilter\LoginInputFilter;
use Authentication\Form\InputFilter\RegisterInputfilter;
use Authentication\Options\Factory\ModuleOptionsFactory;
use Authentication\Options\ModuleOptions;
use Authentication\Service\ApiAuthenticateService;
use Authentication\Service\AuthMailtrapService;
use Authentication\Service\Factory\ApiAuthenticateServiceFactory;
// use Authentication\Service\AuthenticationService as AuthService;
use Authentication\Service\Factory\AuthenticateFactory;
use Authentication\Service\Factory\AuthenticateServiceFactory;
use Authentication\Service\Factory\AuthMailtrapServiceFactory;
use Authentication\Service\Factory\JWTConfigurationFactory;
use Authentication\Service\Factory\JWTIssuerFactory;
use Authentication\Service\Factory\RegisterServiceFactory;
use Authentication\Service\JWTConfiguration;
use Authentication\Service\JWTIssuer;
use Authentication\Service\RegisterService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'router' => [
        'routes' => [
            'authentication' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/[:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[a-zA-Z0-9_-]*'
                    ],
                    'defaults' => [
                        'controller' => AuthenticateController::class,
                        'action'     => 'login',
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
            'logout' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/logout',
                    'defaults' => [

                        'controller' => AuthenticateController::class,
                        'action' => 'logout'
                    ]
                ]
            ],

            'api-auth' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/auth[/:interface[/:action[/:id]]]',
                    'constraints' => [
                        'interface' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[a-zA-Z0-9]*'
                    ],
                    'defaults' => [
                        'controller' => ApiauthenticateController::class,
                        "interface" => "ipa",
                        'action'     => 'login',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            AuthenticateController::class => AuthenticateControllerFactory::class,
            ApiauthenticateController::class => ApiauthenticateControllerFactory::class
        ],
    ],

    "form_manager" => [
        "factories" => [
            // fieldset
            UserFieldset::class => UserFieldsetFactory::class
        ],
    ],
    'input_filters' => [

        "factories" => [
            RegisterInputfilter::class => RegisterInputfilterFactory::class,
            LoginInputFilter::class => InvokableFactory::class
        ]
    ],

    "service_manager" => [
        "factories" => [
            // vendor authentication service
            "Laminas\Authentication\AuthenticationService" => AuthenticateFactory::class,
            // authentication service used internally by app
            "app_authenticate_service" => AuthenticateServiceFactory::class,
            "api_authentication_service" => ApiAuthenticateServiceFactory::class,
            JWTConfiguration::class => JWTConfigurationFactory::class,
            JWTIssuer::class => JWTIssuerFactory::class,
            ModuleOptions::class => ModuleOptionsFactory::class,
            ApiAuthenticateService::class => ApiAuthenticateServiceFactory::class,
            RegisterService::class => RegisterServiceFactory::class,
            AuthMailtrapService::class => AuthMailtrapServiceFactory::class

        ],

        "aliases" => [
            "authenticate_module_option" => ModuleOptions::class,
            "authentication_service" => "Laminas\Authentication\AuthenticationService"
        ]

    ],


    'view_manager' => [

        'template_map' => [
            "login-layout" => __DIR__ . "/../view/layout/login_layout.phtml",
            "others-layout" => __DIR__ . "/../view/layout/others_layout.phtml"
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
        'strategies' => array(
            'ViewJsonStrategy'
        )
    ],

    'doctrine' => [
        'configuration' => [
            'orm_default' => [
                'generate_proxies' => true
            ]
        ],
        'authentication' => [
            'orm_default' => [
                'object_manager' => EntityManager::class,
                'identity_class' => "Authentication\Entity\User",
                'identity_property' => 'email',
                'credential_property' => 'password',
                'credential_callable' => "Authentication\Service\AuthenticationService::verifyHashedPassword"
            ],
        ],

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
    ],

    'rabbitmq' => [
        'producer' => [
            'login-trig' => [
                'connection' => 'default', // the connection name
                'exchange' => [
                    'type' => 'direct',
                    'name' => 'imappv2',
                    'durable' => true,      // (default)
                    'auto_delete' => false, // (default)
                    'internal' => false,    // (default)
                    'no_wait' => false,     // (default)
                    'declare' => true,      // (default)
                    'arguments' => [],      // (default)
                    'ticket' => 0,          // (default)
                    'exchange_binds' => []  // (default)
                ],
                'queue' => [ // optional queue
                    'name' => 'email-imapp', // can be an empty string,
                    'type' => null,         // (default)
                    'passive' => false,     // (default)
                    'durable' => true,      // (default)
                    'auto_delete' => false, // (default)
                    'exclusive' => false,   // (default)
                    'no_wait' => false,     // (default)
                    'arguments' => [],      // (default)
                    'ticket' => 0,          // (default)
                    'routing_keys' => []    // (default)
                ],
                'auto_setup_fabric_enabled' => true // auto-setup exchanges and queues
            ]
        ]
    ]
];
