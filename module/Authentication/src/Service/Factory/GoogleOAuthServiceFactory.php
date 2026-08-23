<?php

namespace Authentication\Service\Factory;

use Authentication\Service\GoogleOAuthService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class GoogleOAuthServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): GoogleOAuthService
    {
        $config = $container->get('config');
        return new GoogleOAuthService($config);
    }
}
