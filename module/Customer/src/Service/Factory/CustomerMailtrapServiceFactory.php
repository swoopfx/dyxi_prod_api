<?php

namespace Customer\Service\Factory;

use Customer\Service\CustomerMailtrapService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class CustomerMailtrapServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $xserv = new CustomerMailtrapService();
        $config = $container->get("config");
        /**
         * @var GeneralService
         */
        $generalService = $container->get("general_service");
        $xserv->setAppConfig($config)->setMailtrapConfig($generalService->getSettings());
        return $xserv;
    }
}
