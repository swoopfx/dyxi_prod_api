<?php

namespace Authentication\Options\Factory;

use Authentication\Options\ModuleOptions;
use Psr\Container\ContainerInterface;
use General\Service\GeneralService;
// use InvalidArgumentException;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ModuleOptionsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config = $container->get("config");
        if (! $container->has("general_service")) {
            throw new \InvalidArgumentException("Module options cannot retrieve general service");
        }
        /**
         * @var GeneralService
         */
        $generalService = $container->get("general_service");
        $options = new ModuleOptions($config);
        $options->setEntityManager($generalService->getEm());
        return $options;
    }
}
