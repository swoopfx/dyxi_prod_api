<?php declare(strict_types=1);

namespace Authentication\Service\Factory;

use Authentication\Service\GoogleAuthService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class GoogleAuthServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return new GoogleAuthService(
            $container->get('config')
        );
    }
}
