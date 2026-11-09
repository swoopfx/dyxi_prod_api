<?php

namespace Game\Service\Factory;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Doctrine\ORM\EntityManager;
use Game\Service\GameService;
use Game\Service\GameBigQueryService;
use Game\Service\CurriculumService;
use General\Service\RedisCacheService;
use General\Service\BigQueryService;

class GameServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $entityManager = $container->get(EntityManager::class);
        $redisCacheService = $container->has(RedisCacheService::class) ? $container->get(RedisCacheService::class) : null;
        $curriculumService = $container->get(CurriculumService::class);
        /** @var BigQueryService|null $bigQueryService */
        $bigQueryService = $container->has(BigQueryService::class)
            ? $container->get(BigQueryService::class)
            : ($container->has('bigquery_service') ? $container->get('bigquery_service') : null);

        /** @var GameBigQueryService|null $gameBigQueryService */
        $gameBigQueryService = $container->has(GameBigQueryService::class)
            ? $container->get(GameBigQueryService::class)
            : ($container->has('game_bigquery_service') ? $container->get('game_bigquery_service') : null);

        if (!$gameBigQueryService && $bigQueryService) {
            $gameBigQueryService = new GameBigQueryService($bigQueryService);
        }

        return new GameService($entityManager, $redisCacheService, $curriculumService, $bigQueryService, $gameBigQueryService);
    }
}
