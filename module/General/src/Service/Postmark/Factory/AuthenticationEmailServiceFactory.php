<?php

namespace General\Service\Postmark\Factory;

// use Exception;

use General\Service\Postmark\AuthenticationEmailService;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AuthenticationEmailServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $xserv = new AuthenticationEmailService();
        // $config = ;
        // if (! $container->has("config")["postmark"]) {
        //     throw new \Exception(" General\Service\Postmark\Factory\AuthenticationEmailServiceFactory could not get postmark config");
        // }
        $config = $container->get("config");
        $postmarkConfig = $config["postmark"] ?? [];
        $apikey = $postmarkConfig["live"]["authentication_service"]["apikey"] ?? '';
        $sender = $postmarkConfig["live"]["sender_email"] ?? '';
        $xserv->setPostmarkConfig($postmarkConfig)
        ->setApiToken($apikey)->setSender($sender);
        return $xserv;
    }
}
