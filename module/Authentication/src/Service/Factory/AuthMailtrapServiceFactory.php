<?php

namespace Authentication\Service\Factory;

use Authentication\Service\AuthMailtrapService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use General\Service\GeneralService;
use Psr\Container\ContainerInterface;

class AuthMailtrapServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $xserv = new AuthMailtrapService();
        $config = $container->get("config");
        /**
         * @var GeneralService
         */
        $generalService = $container->get("general_service");
        $xserv->setMailtrapconfig($generalService->getSettings())->setAppConfig($config);
        return $xserv;
    }
}
