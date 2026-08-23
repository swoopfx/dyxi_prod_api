<?php

namespace Customer\Service\Factory;

use Authentication\Service\ApiAuthenticateService;
use Customer\Service\CustomerService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class CustomerServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $xserv = new CustomerService();
        if (! $container->get("general_service")) {
            throw new \Exception("General Service absent");
        }
        $generalService = $container->get("general_service");
        $xserv->setEntityManager($generalService->getEm())->setApiAuth($container->get(ApiAuthenticateService::class));
        return $xserv;
    }
}
