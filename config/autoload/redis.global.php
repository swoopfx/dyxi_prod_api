<?php

declare(strict_types=1);

return [
    'redis' => [
        'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
        'port' => (int)(getenv('REDIS_PORT') ?: 6379),
        'database' => (int)(getenv('REDIS_DB') ?: 0),
        'ttl' => (int)(getenv('REDIS_TTL') ?: 86400),
        'namespace' => getenv('REDIS_NAMESPACE') ?: 'dyxi_general',
    ],
    'caches' => [
        // Authorization RBAC Namespace
        'authorization_redis_cache' => [
            'adapter' => 'redis',
            'options' => [
                'server' => [
                    'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
                    'port' => (int)(getenv('REDIS_PORT') ?: 6379),
                ],
                'ttl' => (int)(getenv('REDIS_TTL') ?: 86400),
                'namespace' => getenv('REDIS_AUTH_NAMESPACE') ?: 'dyxi_rbac',
            ],
            'plugins' => [
                [
                    'name' => 'exception_handler',
                    'options' => [
                        'throw_exceptions' => false,
                    ],
                ],
                [
                    'name' => 'serializer',
                ],
            ],
        ],

        // Curriculum Service Namespace (0 = forever / no expiration)
        'curriculum_redis_cache' => [
            'adapter' => 'redis',
            'options' => [
                'server' => [
                    'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
                    'port' => (int)(getenv('REDIS_PORT') ?: 6379),
                ],
                'ttl' => (int)(getenv('REDIS_CURRICULUM_TTL') ?: 0),
                'namespace' => getenv('REDIS_CURRICULUM_NAMESPACE') ?: 'dyxi_curriculum',
            ],
            'plugins' => [
                [
                    'name' => 'exception_handler',
                    'options' => [
                        'throw_exceptions' => false,
                    ],
                ],
                [
                    'name' => 'serializer',
                ],
            ],
        ],

        // General Multi-Purpose Namespace
        'general_redis_cache' => [
            'adapter' => 'redis',
            'options' => [
                'server' => [
                    'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
                    'port' => (int)(getenv('REDIS_PORT') ?: 6379),
                ],
                'ttl' => (int)(getenv('REDIS_TTL') ?: 86400),
                'namespace' => getenv('REDIS_NAMESPACE') ?: 'dyxi_general',
            ],
            'plugins' => [
                [
                    'name' => 'exception_handler',
                    'options' => [
                        'throw_exceptions' => false,
                    ],
                ],
                [
                    'name' => 'serializer',
                ],
            ],
        ],
    ],
];
