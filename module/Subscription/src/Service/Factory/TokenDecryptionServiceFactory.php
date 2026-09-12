<?php

namespace Subscription\Service\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Subscription\Service\TokenDecryptionService;

class TokenDecryptionServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $hexKey = $config['subscription']['central_hex_key'] ?? getenv('CENTRAL_HEX_KEY') ?: null;

        return new TokenDecryptionService($hexKey);
    }
}
