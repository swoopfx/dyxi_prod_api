<?php

namespace Authentication\Controller\Factory;

use Authentication\Controller\ApiauthenticateController;
use Authentication\Service\ApiAuthenticateService;
use Authentication\Service\AuthMailtrapService;
use Authentication\Service\JWTIssuer;
use Authentication\Service\RegisterService;
use Authentication\Service\GoogleOAuthService;
use General\Service\Postmark\AuthenticationEmailService;
use General\Service\GeneralService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Exception;
use InvalidArgumentException;

class ApiauthenticateControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $ctr = new ApiauthenticateController();
        if (!$container->has(ApiAuthenticateService::class)) {
            throw new \InvalidArgumentException('Api Authetication Service cannot be retrieved at Api authentication controller');
        }
        if (!$container->has('general_service')) {
            throw new \InvalidArgumentException('General Service cannot be retrieved at Api authentication controller');
        }
        if (!$container->has(RegisterService::class)) {
            throw new \Exception('API RegisterService cannot retirve RegisterService::class');
        }

        if (!$container->has('mailtrap_service')) {
            throw new \Exception('API MailtrapService Factory cannot retirve MailtrapService::class');
        }
        if (!$container->has(GoogleOAuthService::class)) {
            throw new \Exception('API GoogleOAuthService cannot retirve GoogleOAuthService::class');
        }

        $config = $container->get('config');
        $registerService = $container->get(RegisterService::class);
        $apiAutheticateService = $container->get(ApiAuthenticateService::class);
        $jwtIssuer = $container->get(JWTIssuer::class);
        /** @var GeneralService */
        $generalService = $container->get('general_service');
        $mailtrapService = $container->get('mailtrap_service');
        $authMailTrapService = $container->get(AuthMailtrapService::class);
        $googleAuthService = $container->get(GoogleOAuthService::class);

        $ctr
            ->setGeneralService($generalService)
            ->setApiAuthService($apiAutheticateService)
            ->setEntityManager($generalService->getEm())
            ->setMailtrapService($mailtrapService)
            ->setAuthMailtrapService($authMailTrapService)
            ->setAuthPostmarkService($container->get(AuthenticationEmailService::class))
            ->setGoogleAuthService($googleAuthService)
            ->setConfig($config)
            ->setRegisterService($registerService);

        return $ctr;
    }
}
