<?php

namespace Wallet\Controller;

use Authentication\Service\ApiAuthenticateService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Wallet\Entity\Wallet;
use Wallet\Service\WalletApiService;

class WalletApiController extends AbstractActionController
{
    /**
     * Undocumented variable
     *
     * @var EntityManager
     */
    private $entityManager;


    /**
     * Api Authenitication
     *
     * @var ApiAuthenticateService
     */
    private $apiAuth;


    /**
     * Undocumented variable
     *
     * @var WalletApiService
     */
    private $walletApiService;

    public function indexAction()
    {
        $jsonModel = new JsonModel();
        return $jsonModel;
    }

    /**
     * Used to retrieve list of gender
     * @OA\GET( path="/wallet/api/get-wallet", tags={"Wallet"}, description="retrives wallet",
     *
     * @OA\Response(response="200", description="Success"),
     * @OA\Response(response="400", description="Bad Request"),
     * @OA\Response(response="401", description="Not Authorized"),
     * @OA\Response(response="403", description="Not permitted"),
     * security={{"bearerAuth":{}}}
     * )
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function getWalletAction()
    {
        $jsonModel = new JsonModel();
        $em = $this->entityManager;
        $activeUser = $this->apiAuth->getContainerIdentity();
        $data = $em->getRepository(Wallet::class)->createQueryBuilder("w")
            ->select(["w.walletUuid as wallect_uuid", "w.balance as wallet_credit", "u"])
            ->leftJoin("w.user", "u")
            ->where("u.uuid = :uuid")->setParameters([
                "uuid" => $activeUser["uuid"]
            ])->getQuery()->getResult(Query::HYDRATE_ARRAY);

        $jsonModel->setVariables([
            "data" => $data[0]
        ]);
        return $jsonModel;
    }

    /**
     * Gets the balance of an active user
     * @OA\GET( path="/wallet/api/get-balance", tags={"Wallet"}, description="retrives wallet",
     *
     * @OA\Response(response="200", description="Success"),
     * @OA\Response(response="400", description="Bad Request"),
     * @OA\Response(response="401", description="Not Authorized"),
     * @OA\Response(response="403", description="Not permitted"),
     * security={{"bearerAuth":{}}}
     * )
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function getBalanceAction()
    {
        $walletService = $this->walletApiService;
        $jsonModel = new JsonModel();
        $response = $this->getResponse();
        try {
            $jsonModel->setVariables([
                "data" => $walletService->retrieveWalletBalance()
            ]);
        } catch (\Throwable $th) {
            $jsonModel->setVariables([
                "success" => false,
                "description" => $th->getMessage()
            ]);
        }
        return $jsonModel;
    }

    /**
     * Get the value of entityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
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
     * Get api Authenitication
     *
     * @return  ApiAuthenticateService
     */
    public function getApiAuth()
    {
        return $this->apiAuth;
    }

    /**
     * Set api Authenitication
     *
     * @param  ApiAuthenticateService  $apiAuth  Api Authenitication
     *
     * @return  self
     */
    public function setApiAuth(ApiAuthenticateService $apiAuth)
    {
        $this->apiAuth = $apiAuth;

        return $this;
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
