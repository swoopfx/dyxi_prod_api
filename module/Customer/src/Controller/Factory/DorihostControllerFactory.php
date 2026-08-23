<?php

namespace Customer\Controller\Factory;

use Authentication\Service\ApiAuthenticateService;
use Customer\Controller\DorihostController;
use Customer\Service\DoriHostService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Wallet\Service\WalletApiService;
use Wallet\Service\WalletService;

class DorihostControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $ctr = new DorihostController();
        if (!$container->has("general_service")) {
            throw new \Exception("Dorihost controller factroy could not retriev general service");
        }
        $generalService = $container->get("general_service");
        $walletApiService = $container->get(WalletApiService::class);
        $apiAuth = $container->get(ApiAuthenticateService::class);
        $dorihostService = $container->get(DoriHostService::class);
        $ctr->setEntityManager($generalService->getEm())->setApiAuth($apiAuth)
            ->setDorihostService($dorihostService)
            ->setWalletService($container->get(WalletService::class))
            ->setWalletApiService($walletApiService);
        return $ctr;
    }
}
