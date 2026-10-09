<?php

declare(strict_types=1);

namespace General\Service\Factory;

use General\Service\BigQueryService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class BigQueryServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): BigQueryService
    {
        $config = $container->has('config') ? $container->get('config') : [];
        return new BigQueryService((array) $config);
    }
}
