<?php

namespace Wallet\Service\Factory;

use Authentication\Service\ApiAuthenticateService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;
use Wallet\Service\WalletService;
use Authentication\Entity\User;
use Wallet\Entity\Wallet;
use Wallet\Service\WalletApiService;

class WalletApiServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $xserv = new WalletApiService();
        $apiAuthService = $container->get(ApiAuthenticateService::class);
        $activeUser = $apiAuthService->getContainerIdentity();
        $generalService = $container->get("general_service");
        /**
         * @var EntityManager
         */
        $em = $generalService->getEm();
        $userEntity = $em->getRepository(User::class)->findOneBy([
            "uuid" => $activeUser["uuid"]
        ]);
        $walletEntity = $userEntity->getWallet();
        if ($userEntity->getWallet() == null) {
            $walletEntity = new Wallet();
            $walletEntity->setCreatedOn(new \Datetime())
                ->setBalance("0")
                ->setUser($userEntity)
                ->setWalletUid(WalletApiService::createWalletUid())
                ->setWalletUuid(WalletApiService::createWalletUuid());

            $em->persist($walletEntity);
            $em->flush();
        }
        $xserv->setGeneralService($generalService)
            ->setEntityManager($em)
            ->setUserEntity($userEntity)
            ->setWalletEntity($walletEntity);
        return $xserv;
    }
}
