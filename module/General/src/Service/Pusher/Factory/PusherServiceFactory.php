<?php

namespace General\Service\Pusher\Factory;

use General\Service\Pusher\PusherService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Pusher\Pusher;
use General\Service\GeneralService;
use General\Entity\Settings;

class PusherServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $xserv = new PusherService();

        if (! $container->has("general_service")) {
            throw new \Exception("Pusher Factory  could not retrive general service");
        }
        /**
         * @var GeneralService
         */
        $generalService = $container->get("general_service");
        /**
         * @var Settings
         */
        $setting = $generalService->getSettings();
        $pusherObject = new Pusher(
            strip_tags($setting->getPusherAppKey()),
            strip_tags($setting->getPusherSecretKey()),
            strip_tags($setting->getPusherAppId()),
            [
                "cluster" => $setting->getPusherAppCluster(),
            ]
        );
        $xserv->setPusherObject($pusherObject);
        return $xserv;
    }
}
