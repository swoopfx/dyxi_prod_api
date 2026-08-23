<?php

namespace Wallet\Service;

use Doctrine\ORM\EntityManager;
use Authentication\Entity\User;
use Authentication\Service\ApiAuthenticateService;
use Ramsey\Uuid\Uuid;
use Wallet\Entity\Wallet;
use Wallet\Exceptions\InsufficientBalanceException;

class WalletApiService
{
    /**
     * Undocumented variable
     *
     * @var EntityManager
     */
    private $entityManager;


    private $generalService;

    /**
     * Undocumented variable
     *
     * @var User
     */
    private $userEntity;

    /**
     * Undocumented variable
     *
     * @var Wallet
     */
    private $walletEntity;

    public function creditWallet($data)
    {
        $em = $this->entityManager;
        $walletEntity = $this->walletEntity;
        $walletEntity->setBalance($data["credit"])->setUpdatedOn(new \Datetime());

        $em->persist($walletEntity);
        $em->flush();
    }


    public function debitWallet($data)
    {
        $em = $this->entityManager;
        $walletEntity = $this->walletEntity;
        $balance = $walletEntity->getBalance();
        if ($balance < $data["debit"]) {
            throw new  InsufficientBalanceException("Insufficient Balance");
        }

        $newBalance = $balance - $data["debit"];
        $walletEntity->setBalance($newBalance)->setUpdatedOn(new \Datetime());

        $em->persist($walletEntity);
        $em->flush();
    }


    public function retrieveWalletBalance()
    {
        // $em = $this->entityManager;
        $walletEntity = $this->walletEntity;
        return  $walletEntity->getBalance();
    }


    public static function createWalletUuid()
    {

        $var = Uuid::uuid4();
        return $var->toString();
    }

    public static function createWalletUid()
    {
        return uniqid("wallet");
    }




    /**
     * Set the value of entityManager
     *
     * @return  self
     */
    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;

        return $this;
    }

    /**
     * Set the value of generalService
     *
     * @return  self
     */
    public function setGeneralService($generalService)
    {
        $this->generalService = $generalService;

        return $this;
    }

    /**
     * Set undocumented variable
     *
     * @param  Wallet  $walletEntity  Undocumented variable
     *
     * @return  self
     */
    public function setWalletEntity(Wallet $walletEntity)
    {
        $this->walletEntity = $walletEntity;

        return $this;
    }

    /**
     * Set undocumented variable
     *
     * @param  User  $userEntity  Undocumented variable
     *
     * @return  self
     */
    public function setUserEntity(User $userEntity)
    {
        $this->userEntity = $userEntity;

        return $this;
    }
}
