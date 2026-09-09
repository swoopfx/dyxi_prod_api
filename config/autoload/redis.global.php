<?php
declare(strict_types=1);

return [
    'redis' => [
        'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
        'port' => (int)(getenv('REDIS_PORT') ?: 6379),
        'database' => (int)(getenv('REDIS_DB') ?: 0),
        'ttl' => 86400, // 24 hours default cache TTL
        'namespace' => 'dyxi_general',
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
                'ttl' => 86400,
                'namespace' => 'dyxi_rbac',
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
                'ttl' => 0, // 0 = forever / no expiration
                'namespace' => 'dyxi_curriculum',
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
                'ttl' => 86400,
                'namespace' => 'dyxi_general',
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

