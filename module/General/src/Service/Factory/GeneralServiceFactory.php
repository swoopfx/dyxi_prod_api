<?php

namespace General\Service\Factory;

use Authentication\Service\ApiAuthenticateService;
use Doctrine\ORM\EntityManager;
use General\Service\GeneralService;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class GeneralServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $xserv = new GeneralService();

        if (!$container->get("authentication_service")) {
            throw new \InvalidArgumentException("General Service Factory cannot reach Authentication Service");
        }

        $em = $container->get(EntityManager::class);
        $authService = $container->get("authentication_service");

        $xserv->setEm($em)->setAuthService($authService);

        return $xserv;
    }
}
