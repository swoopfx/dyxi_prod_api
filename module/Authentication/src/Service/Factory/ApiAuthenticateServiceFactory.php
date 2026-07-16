<?php

namespace Authentication\Service\Factory;

use Authentication\Form\InputFilter\LoginInputFilter;
use Authentication\Form\InputFilter\RegisterInputfilter;
use Authentication\Service\ApiAuthenticateService;
use Exception;
use InvalidArgumentException;
use General\Service\GeneralService;
use General\Service\Postmark\AuthenticationEmailService;
use Laminas\InputFilter\InputFilterPluginManager;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Wallet\Service\WalletApiService;

class ApiAuthenticateServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $xserv = new ApiAuthenticateService();
        if (! $container->has("General\Service\GeneralService")) {
            throw new \InvalidArgumentException("Api Authentication Service cannot retrieve general service");
        }

        if (! $container->has("Authentication\Service\JWTIssuer")) {
            throw new InvalidArgumentException("Api Authentication Service cannot retrieve jwt issuer service");
        }
        if (! $container->has("Laminas\Authentication\AuthenticationService")) {
            throw new \Exception("Api authentication service cannot retrieve auth service");
        }
        if (! $container->has(WalletApiService::class)) {
            throw new \Exception("Api authentication service cannot retrieve Wallet ApiService");
        }
        /**
         * @var  InputFilterPluginManager
         */
        $inputFilterManager = $container->get(InputFilterPluginManager::class);

        if (! $inputFilterManager->has(LoginInputFilter::class)) {
            throw new InvalidArgumentException("Api Authentication Service could not retrieve the Login Input Filter");
        }

        if (! $inputFilterManager->has(RegisterInputfilter::class)) {
            throw new InvalidArgumentException("Api Authentication Service could not retrieve the Register Input Filter");
        }

        $jwtIssuer = $container->get("Authentication\Service\JWTIssuer");
        $registerInputFilter = $inputFilterManager->get(RegisterInputfilter::class);
        $logininputFiler = $inputFilterManager->get(LoginInputFilter::class);
        /**
         * @var GeneralService
         */
        $generalService = $container->get("General\Service\GeneralService");
        $requestObject = $container->get("Request");
        $responseObject = $container->get("Response");
        $authenticationService = $generalService->getAuthService();
        // $authEmailService = $container->get(AuthenticationEmailService::class)
        // $walletService = $container->get(WalletApiService::class);

        $xserv->setEntityManager($generalService->getEm())
            ->setLoginInputFilter($logininputFiler)
            ->setJwtIssuer($jwtIssuer)
            ->setRequestObject($requestObject)
            ->setResponseObject($responseObject)
            ->setAuthenticationService($authenticationService)
            // ->setWalletService($walletService)
            ->setRegisterInputFilter($registerInputFilter);
        return $xserv;
    }
}
