<?php

namespace Subscription\Service\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Doctrine\ORM\EntityManager;
use Subscription\Service\TokenDecryptionService;
use Subscription\Service\SubscriptionService;

class SubscriptionServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $entityManager = $container->get(EntityManager::class);
        $tokenService  = $container->get(TokenDecryptionService::class);
        $config        = $container->get('config');

        $paystackSecretKey = $config['subscription']['paystack_secret_key'] ?? getenv('PAYSTACK_SECRET_KEY') ?: null;

        return new SubscriptionService($entityManager, $tokenService, $paystackSecretKey);
    }
}
