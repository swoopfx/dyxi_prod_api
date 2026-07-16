<?php

declare(strict_types=1);

namespace Resources\Controller\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Resources\Controller\BillingController;

class BillingControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return new BillingController();
    }
}
