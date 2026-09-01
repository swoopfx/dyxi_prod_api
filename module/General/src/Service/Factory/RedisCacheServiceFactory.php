<?php
declare(strict_types=1);

namespace General\Service\Factory;

use General\Service\RedisCacheService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class RedisCacheServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): RedisCacheService
    {
        $config = $container->get('config');
        return new RedisCacheService($config);
    }
}
