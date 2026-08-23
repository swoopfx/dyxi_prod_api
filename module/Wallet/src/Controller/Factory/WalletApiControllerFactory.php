<?php

namespace Wallet\Controller\Factory;

use Authentication\Service\ApiAuthenticateService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Wallet\Controller\WalletApiController;
use Wallet\Service\WalletApiService;

class WalletApiControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $ctr = new WalletApiController();
        $generalService = $container->get("general_service");
        $apiAuthentication = $container->get(ApiAuthenticateService::class);
        $walletApiService = $container->get(WalletApiService::class);
        $ctr->setEntityManager($generalService->getEm())
            ->setApiAuth($apiAuthentication)
            ->setWalletApiService($walletApiService);
        return $ctr;
    }
}
