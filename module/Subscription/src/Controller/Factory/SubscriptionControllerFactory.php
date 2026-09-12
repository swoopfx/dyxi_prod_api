<?php

namespace Subscription\Controller\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Subscription\Service\SubscriptionService;
use Subscription\Controller\SubscriptionController;

class SubscriptionControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $subscriptionService = $container->get(SubscriptionService::class);
        return new SubscriptionController($subscriptionService);
    }
}
