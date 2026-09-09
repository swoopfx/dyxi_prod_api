<?php

namespace General\Service\Pusher\Factory;

use General\Service\Pusher\PusherService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Pusher\Pusher;
use General\Service\GeneralService;

class PusherServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $xserv = new PusherService();

        if (! $container->has("general_service")) {
            throw new \Exception("Pusher Factory could not retrieve general service");
        }

        /** @var GeneralService $generalService */
        $generalService = $container->get("general_service");
        $setting = method_exists($generalService, 'getSettings') ? $generalService->getSettings() : null;

        $appKey = getenv('PUSHER_APP_KEY') ?: ($setting && method_exists($setting, 'getPusherAppKey') ? strip_tags((string)$setting->getPusherAppKey()) : 'dummy_key');
        $secretKey = getenv('PUSHER_APP_SECRET') ?: ($setting && method_exists($setting, 'getPusherSecretKey') ? strip_tags((string)$setting->getPusherSecretKey()) : 'dummy_secret');
        $appId = getenv('PUSHER_APP_ID') ?: ($setting && method_exists($setting, 'getPusherAppId') ? strip_tags((string)$setting->getPusherAppId()) : 'dummy_id');
        $appCluster = getenv('PUSHER_APP_CLUSTER') ?: ($setting && method_exists($setting, 'getPusherAppCluster') ? $setting->getPusherAppCluster() : 'mt1');

        $pusherObject = new Pusher(
            $appKey,
            $secretKey,
            $appId,
            [
                "cluster" => $appCluster,
            ]
        );
        $xserv->setPusherObject($pusherObject);
        return $xserv;
    }
}
