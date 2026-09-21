<?php

namespace General\Service\Postmark\Factory;

use General\Service\Postmark\PostmarkGeneralMailService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class PostmarkGeneralMailServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $xserv = new PostmarkGeneralMailService();
        $config = $container->get("config");
        $postmarkConfig = $config["postmark"] ?? [];
        $apikey = $postmarkConfig["live"]["authentication_service"]["apikey"] ?? '';
        $sender = $postmarkConfig["live"]["sender_email"] ?? '';
        $xserv->setPostmarkConfig($postmarkConfig)
            ->setApiToken($apikey)->setSender($sender);
        return $xserv;
    }
}
