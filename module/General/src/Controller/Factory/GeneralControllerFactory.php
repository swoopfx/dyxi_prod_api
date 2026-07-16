<?php

namespace General\Controller\Factory;

use Authentication\Service\ApiAuthenticateService;
use Exception;
use General\Controller\GeneralController;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class GeneralControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $ctr = new GeneralController();
        if (! $container->has("general_service")) {
            throw new \Exception("General Controller cannot retrieve General Service");
        }
        $generalService = $container->get("general_service");
        // $apiAuth = $container->get(ApiAuthenticateService::class);
        $ctr->setEntityManager($generalService->getEm())
            ->setGeneralService($generalService)
            ->setApiAuth($container->get(ApiAuthenticateService::class));
        return $ctr;
    }
}
