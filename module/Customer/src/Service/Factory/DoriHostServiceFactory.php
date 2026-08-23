<?php

namespace Customer\Service\Factory;

use Authentication\Service\ApiAuthenticateService;
use Customer\Service\DoriHostService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Wallet\Service\WalletApiService;

class DoriHostServiceFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $xserv = new DoriHostService();
        $generalService = $container->get("general_service");
        $apiAuthService = $container->get(ApiAuthenticateService::class);
        $walletApiService = $container->get(WalletApiService::class);
        $xserv->setApiAuthService($apiAuthService)
            ->setWalletApiService($walletApiService)
            ->setEntityManager($generalService->getEm());
        return $xserv;
    }
}
