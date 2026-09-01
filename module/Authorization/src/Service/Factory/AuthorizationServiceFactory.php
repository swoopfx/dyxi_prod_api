<?php
declare(strict_types=1);

namespace Authorization\Service\Factory;

use Authorization\Service\AuthorizationService;
use Interop\Container\ContainerInterface;
use Laminas\Cache\Storage\StorageFactory;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AuthorizationServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): AuthorizationService
    {
        $generalService = $container->get('general_service');
        $em = $generalService->getEm();

        $cacheStorage = null;
        try {
            if ($container->has('authorization_redis_cache')) {
                $cacheStorage = $container->get('authorization_redis_cache');
            } elseif ($container->has('Laminas\Cache\Storage\Adapter\Redis')) {
                $cacheStorage = $container->get('Laminas\Cache\Storage\Adapter\Redis');
            } else {
                $config = $container->get('config');
                $redisConfig = $config['caches']['authorization_redis_cache'] ?? [
                    'adapter' => [
                        'name' => 'redis',
                        'options' => [
                            'server' => [
                                'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
                                'port' => (int)(getenv('REDIS_PORT') ?: 6379),
                            ],
                            'ttl' => 86400,
                            'namespace' => 'dyxi_rbac',
                        ],
                    ],
                ];
                $cacheStorage = StorageFactory::factory($redisConfig);
            }
        } catch (\Throwable $e) {
            $cacheStorage = null;
        }

        return new AuthorizationService($em, $cacheStorage);
    }
}
