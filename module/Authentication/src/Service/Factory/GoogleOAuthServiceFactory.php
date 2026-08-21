<?php

namespace Authentication\Service\Factory;

use Authentication\Service\GoogleOAuthService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Psr\Http\Client\ClientInterface;

class GoogleOAuthServiceFactory implements FactoryInterface
{
    public function __invoke(ServiceLocatorInterface $serviceLocator, $requestedName, ?array $options = null): GoogleOAuthService
    {
        $config = $serviceLocator->get('config');
        $googleConfig = $config['google_oauth'] ?? [];
        return new GoogleOAuthService($googleConfig);
    }
}
