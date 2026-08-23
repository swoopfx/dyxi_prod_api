<?php

namespace Customer\Controller\Factory;

use Authentication\Service\ApiAuthenticateService;
use Customer\Controller\MyrequestController;
use Customer\Service\CustomerService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Wallet\Service\WalletApiService;

class MyrequestControllerFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $ctr = new MyrequestController();
        if (!$container->has("general_service")) {
            throw new \InvalidArgumentException("General Service cannot be retrieved at Customer controller Factory");
        }
        $generalService = $container->get("general_service");
        $apiAuthService = $container->get(ApiAuthenticateService::class);
        $walletApiService = $container->get(WalletApiService::class);
        $customerService = $container->get(CustomerService::class);

        $ctr->setEntityManager($generalService->getEm())
            ->setCustomerService($customerService)
            ->setApiAuth($apiAuthService)
            ->setWalletApiService($walletApiService);
        return $ctr;
    }
}
