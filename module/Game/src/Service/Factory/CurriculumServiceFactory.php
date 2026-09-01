<?php
declare(strict_types=1);

namespace Game\Service\Factory;

use Doctrine\ORM\EntityManager;
use Game\Service\CurriculumService;
use General\Service\RedisCacheService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class CurriculumServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): CurriculumService
    {
        $entityManager = $container->get(EntityManager::class);
        $redisCacheService = $container->has(RedisCacheService::class) ? $container->get(RedisCacheService::class) : null;
        return new CurriculumService($entityManager, $redisCacheService);
    }
}
