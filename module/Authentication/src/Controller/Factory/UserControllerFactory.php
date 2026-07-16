<?php

namespace Authentication\Controller\Factory;

use Authentication\Controller\UserController;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class UserControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $ctr = new UserController();

        return $ctr;
    }
}
