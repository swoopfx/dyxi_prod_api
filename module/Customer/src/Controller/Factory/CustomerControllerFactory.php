<?php

namespace Customer\Controller\Factory;

use Authentication\Service\ApiAuthenticateService;
use Customer\Controller\CustomerController;
use Customer\InputFilter\DropOffInputFilter;
use Customer\InputFilter\PickUpInputFilter;
use Customer\Service\CustomerService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use General\Service\GeneralService;
use General\Service\Pusher\PusherService;
use Laminas\InputFilter\InputFilterPluginManager;
use Wallet\Service\WalletApiService;

class CustomerControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $ctr = new CustomerController();
        if (!$container->has("general_service")) {
            throw new \InvalidArgumentException("General Service cannot be retrieved at Customer controller Factory");
        }
        /**
         * @var GeneralService
         */
        $generalService = $container->get("general_service");
        $apiAuthService = $container->get(ApiAuthenticateService::class);
        $inputfilterPlugin = $container->get(InputFilterPluginManager::class);
        $dropoffInputFilter = $inputfilterPlugin->get(DropOffInputFilter::class);
        $pickupInputfilter = $inputfilterPlugin->get(PickUpInputFilter::class);
        $ctr->setEntityManager($generalService->getEm())
            ->setSettings($generalService->getSettings())
            ->setDropoffInputfilter($dropoffInputFilter)
            ->setPickupInpufilter($pickupInputfilter)
            ->setWalletApiService($container->get(WalletApiService::class))
            ->setPusherEvents($generalService->getPusherEvents())
            ->setPusherObject($container->get(PusherService::class))
            ->setCustomerService($container->get(CustomerService::class))
            ->setApiAuth($apiAuthService);
        return $ctr;
    }
}
