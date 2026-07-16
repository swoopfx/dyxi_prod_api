<?php

namespace Authentication\Controller\Factory;

use Authentication\Controller\AuthenticateController;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use General\Service\GeneralService;

class AuthenticateControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $ctr = new AuthenticateController();
        if (! $container->has("app_authenticate_service")) {
            throw new InvalidArgumentException("Authenticate Controller Factory cannot retrieve app_authenticate_service");
        }

        if (! $container->has("General\Service\GeneralService")) {
            throw new InvalidArgumentException("Authenticate Controller Factory cannot retrieve General Service");
        }

        $appAuthService = $container->get("app_authenticate_service");
        // var_dump($appAuthService);

        /**
         * @var GeneralService
         */
        $generalService = $container->get("General\Service\GeneralService");
        // try {
        //     // $rabbit = $container->get("rabbitmq.producer.login-trig");
        // } catch (\Throwable $th) {
        //     echo $th->getMessage();
        // }

        $ctr->setAuthenticateService($appAuthService)
            ->setAuthService($generalService->getAuthService())
            ->setObjectManager($generalService->getObjectManager())
            ->setEm($generalService->getEm());
        return $ctr;
    }
}
