<?php

namespace Game\Controller\Factory;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Game\Service\GameService;
use Authentication\Service\ApiAuthenticateService;
use General\Service\BigQueryService;
use Game\Controller\GameController;

class GameControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $gameService = $container->get(GameService::class);
        $apiAuthService = $container->get(ApiAuthenticateService::class);
        /** @var BigQueryService|null $bigQueryService */
        $bigQueryService = $container->has(BigQueryService::class)
            ? $container->get(BigQueryService::class)
            : ($container->has('bigquery_service') ? $container->get('bigquery_service') : null);

        return new GameController($gameService, $apiAuthService, $bigQueryService);
    }
}
