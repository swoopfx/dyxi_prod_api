<?php

namespace Consultant\Service\Factory;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Doctrine\ORM\EntityManager;
use Consultant\Service\ConsultantService;

class ConsultantServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $entityManager = $container->get(EntityManager::class);
        return new ConsultantService($entityManager);
    }
}
