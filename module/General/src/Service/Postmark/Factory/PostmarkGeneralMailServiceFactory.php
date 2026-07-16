<?php

namespace General\Service\Factory;

use General\Service\PostmarkGeneralMailService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class PostmarkGeneralMailServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $xserv = new PostmarkGeneralMailService();
        $postmarkConfig = $container->get("config")["postmark"];
        $xserv->setPostmarkConfig($postmarkConfig)
            ->setApiToken($postmarkConfig["live"]["authentication_service"]["apikey"])->setSender($postmarkConfig["live"]["sender_email"]);
        return $xserv;
    }
}
