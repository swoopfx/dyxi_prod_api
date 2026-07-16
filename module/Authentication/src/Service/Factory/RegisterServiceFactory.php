<?php

namespace Authentication\Service\Factory;

use Authentication\Form\InputFilter\RegisterInputfilter;
use Authentication\Service\AuthMailtrapService;
use Authentication\Service\RegisterService;
use Exception;
use General\Service\Postmark\AuthenticationEmailService;
use Laminas\InputFilter\InputFilterPluginManager;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class RegisterServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $xserv = new RegisterService();
        if (! $container->has("general_service")) {
            throw new \InvalidArgumentException("Register User Service cannot retrieve general service");
        }
        // if ($container->has("postmark_email_authentication_service")) {
        //     throw new \Exception("Register User Service cannot retrieve postmart email authentication service");
        // }
        if (! $container->has("ControllerPluginManager")) {
            throw new \Exception("Register User Service cannot comntroler plugin Manager");
        }
        if (! $container->has("mailtrap_service")) {
            throw new \Exception("Register User Service cannot retrieve mailtrapservice");
        }
        $controllerPluginManager = $container->get("ControllerPluginManager");
        $urlPlugin = $controllerPluginManager->get("Url");
        $inputFilterManager = $container->get(InputFilterPluginManager::class);
        $generalService = $container->get("general_service");
        // $postmarkMailService = $container->get("postmark_email_authentication_service");
        $mailtrapService = $container->get("mailtrap_service");
        $authMailtrapService = $container->get(AuthMailtrapService::class);
        $registerInputFilter = $inputFilterManager->get(RegisterInputfilter::class);
        $authEmailService = $container->get(AuthenticationEmailService::class);
        $xserv->setGeneralService($generalService)
            ->setPostmarkAuthMailService($authEmailService)
            ->setUrlPlugin($urlPlugin)
            ->setAuthmailtrapService($authMailtrapService)
            ->setMailtrapService($mailtrapService)
            ->setRegisterInputFilter($registerInputFilter);
        return $xserv;
    }
}
