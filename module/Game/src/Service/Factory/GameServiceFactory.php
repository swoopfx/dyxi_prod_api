<?php

namespace Game\Service\Factory;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Doctrine\ORM\EntityManager;
use Game\Service\CurriculumService;
use General\Service\RedisCacheService;

class GameServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $entityManager = $container->get(EntityManager::class);
        $redisCacheService = $container->has(RedisCacheService::class) ? $container->get(RedisCacheService::class) : null;
        $curriculumService = $container->get(CurriculumService::class);
        return new GameService($entityManager, $redisCacheService, $curriculumService);
    }
}
