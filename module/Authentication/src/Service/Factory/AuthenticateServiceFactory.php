<?php

namespace Authentication\Service\Factory;

/**
 * This class is used internally by the whole application to reach the authenication service
 * @author kiel
 */

use Authentication\Service\AuthenticationService;
use General\Service\GeneralService;
use Laminas\Authentication\Exception\InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AuthenticateServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        if (! $container->has("Laminas\Authentication\AuthenticationService")) {
            throw new InvalidArgumentException("Authentication Service cannot be reached");
        }
        if (! $container->has("general_service")) {
            throw new \Exception("Authentication Service could not retrieve GeneralService");
        }
        $xserv = new AuthenticationService();
        /**
         * @var GeneralService
         */
        $generalService = $container->get("general_service");
        // $authService =  $container->get("Laminas\Authentication\AuthenticationService");
        $xserv->setAuthenticationService($generalService->getAuthService());
        return $xserv;
    }
}
