<?php

namespace General\Service\Mailtrap\Factory;

use Exception;
use General\Service\Mailtrap\MailtrapService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class MailtrapServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $xserv = new MailtrapService();
        $config = $container->get("config");
        $mailtrapConfig = $config["mailtrap"];
        if (! $container->has("general_service")) {
            throw new \Exception("Mailtrap Factory could not retrieve general service");
        }
        $generalService = $container->get("general_service");
        $xserv->setMailtrapconfig($mailtrapConfig)->setAppConfig($config);
        return $xserv;
    }
}
