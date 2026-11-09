<?php

declare(strict_types=1);

namespace Game\Service\Factory;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use General\Service\BigQueryService;
use Game\Service\GameBigQueryService;

class GameBigQueryServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): GameBigQueryService
    {
        /** @var BigQueryService|null $bigQueryService */
        $bigQueryService = $container->has(BigQueryService::class)
            ? $container->get(BigQueryService::class)
            : ($container->has('bigquery_service') ? $container->get('bigquery_service') : null);

        return new GameBigQueryService($bigQueryService);
    }
}

