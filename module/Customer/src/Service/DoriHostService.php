<?php

namespace Customer\Service;

use Authentication\Service\ApiAuthenticateService;
use Customer\Entity\CollectionTypeWasteType;
use Doctrine\ORM\EntityManager;
use General\Entity\WasteCollectionType;
use General\Entity\WasteType;
use General\Service\GeneralService;
use Wallet\Service\WalletApiService;

class DoriHostService
{

    /**
     * Undocumented variable
     *
     * @var ApiAuthenticateService
     */
    private $apiAuthService;

    /**
     * Undocumented variable
     *
     * @var WalletApiService
     */
    private $walletApiService;

    /**
     * Undocumented variable
     *
     * @var EntityManager
     */
    private $entityManager;


    const POST_WASTE_STATUS_INITIATED = 100;

    const POST_WASTE_STATUS_ASSIGNED = 200;

    const POST_WASTE_STATUS_COMPLETED = 400;

    const POST_WASTE_STATUS_PICKEDUP = 300;

    const POST_WASTE_STATUS_DELIVERED = 500;

    const POST_WASTE_STATUS_BUSTER_POST = 2000;

    public function calculateWasteCollection($data)
    {
        $em = $this->entityManager;

        /**
         * @var CollectionTypeWasteType
         */
        $collectionTypeEntity = $em->getRepository(CollectionTypeWasteType::class)->findBy([
            "wasteType" => $data["waste_type"],
            "collectionType" => $data["waste_collection_type"]
        ]);
        $wasteTypeEntity = $em->find(WasteType::class, $data["waste_type"]);

        return [
            "credit" => floatval($collectionTypeEntity[0]->getPricePerUnit() * $data["unit_value"]),
            "waste_type" => $wasteTypeEntity->getType()
        ];
    }



    /**
     * Get undocumented variable
     *
     * @return  EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * Set undocumented variable
     *
     * @param  EntityManager  $entityManager  Undocumented variable
     *
     * @return  self
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  ApiAuthenticateService
     */
    public function getApiAuthService()
    {
        return $this->apiAuthService;
    }

    /**
     * Set undocumented variable
     *
     * @param  ApiAuthenticateService  $apiAuthService  Undocumented variable
     *
     * @return  self
     */
    public function setApiAuthService(ApiAuthenticateService $apiAuthService)
    {
        $this->apiAuthService = $apiAuthService;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  WalletApiService
     */
    public function getWalletApiService()
    {
        return $this->walletApiService;
    }

    /**
     * Set undocumented variable
     *
     * @param  WalletApiService  $walletApiService  Undocumented variable
     *
     * @return  self
     */
    public function setWalletApiService(WalletApiService $walletApiService)
    {
        $this->walletApiService = $walletApiService;

        return $this;
    }
}
