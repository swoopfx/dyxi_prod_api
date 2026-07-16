<?php

namespace General\Service\Factory;

use Authentication\Service\ApiAuthenticateService;
// use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use General\Entity\Settings;
use General\Service\GeneralService;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class GeneralServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $xserv = new GeneralService();

        if (!$container->get("authentication_service")) {
            throw new \InvalidArgumentException("General Service Factory cannot reach Authentication Service");
        }
        $em = $container->get(EntityManager::class);
        // $objectManager = $container->get('doctrine.documentmanager.odm_default');

        // $objectManager = $container->get(DocumentManager::class);


        $settingEntity = $em->find(Settings::class, 100);
        $authService = $container->get("authentication_service");

        $xserv->setEm($em)->setAuthService($authService)->setSettings($settingEntity);


        return $xserv;
    }
}
