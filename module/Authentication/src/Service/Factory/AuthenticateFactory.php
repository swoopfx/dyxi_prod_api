<?php

namespace Authentication\Service\Factory;

// use Interop\Container\ContainerInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AuthenticateFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return $container->get("doctrine.authenticationservice.orm_default");
    }
}
