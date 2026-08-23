<?php

namespace Customer\Controller;

use Actors\Entity\AssignedRequest;
use Actors\Entity\AssignedRequestStatus;
use Actors\Service\TrashBustterService;
use Authentication\Entity\User;
use Laminas\Mvc\Controller\AbstractActionController;
use Authentication\Service\ApiAuthenticateService;
use Customer\Entity\CollectionTypeWasteType;
use Customer\Entity\HandshakePincode;
use Customer\Entity\WasteRequest;
use Customer\Entity\WasteRequestActivity;
use Customer\Service\CustomerService;
use Wallet\Service\WalletApiService;
use Doctrine\ORM\EntityManager;
use General\Entity\WasteCollectionType;
use General\Entity\WasteRequestActivityConst;
use General\Entity\WasteRequestState;
use General\Service\GeneralService;
use Laminas\View\Model\JsonModel;

class MyrequestController extends AbstractActionController
{

    /**
     * Undocumented variable
     *
     * @var ApiAuthenticateService
     */
    private $apiAuth;

    /**
     * Undocumented variable
     *
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Undocumented variable
     *
     * @var WalletApiService
     */
    private $walletApiService;

    /**
     * Undocumented variable
     *
     * @var CustomerService
     */
    private CustomerService $customerService;



    // public function myRequestsAction()
    // {
    //     $jsonModel = new JsonModel();
    //     $em = $this->entityManager;
    //     $data = $em->getRepository(WasteRequest::class)
    //         ->createQueryBuilder('w')
    //         ->select([])->where("")
    //         ->setParameters([])->getQuery()->getArrayResult();
    //     $jsonModel->setVariables([
    //         "data" => $data
    //     ]);
    //     return $jsonModel;
    // }

    // public function myCompletedRequestAction() {

    // }

    // public function myBookedRequestAction() {

    // }

    // public function viewRequestAction() {
    //     $jsonMode
    // }

    /**
     * Used to generate handshake pin  for customers and dori host 
     * @OA\POST( path="/request/api/generate-pin", tags={"MY REQUEST", "DORIHOST", "TrashBuster"}, description="Used to generate handshake pin  for customers and dori host  ",
     *
     * @OA\RequestBody(
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(required={"wuid"},
     * @OA\Property(property="wuid", type="string", example="wer67-hjurkiu7jj-h234rt", description="This is the waste request uuid")
     * )
     * ),
     * ),
     * @OA\Response(response="201", description="Success"),
     * @OA\Response(response="400", description="Bad Request"),
     * @OA\Response(response="401", description="Not Authorized"),
     * @OA\Response(response="403", description="Not permitted"),
     *
     * security={{"bearerAuth":{}}}
     *
     * )
     *
     * requires
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function generatePinAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $em = $this->entityManager;
        $response = $this->getResponse();
        if ($request->isPost()) {
            $json = $request->getContent();
            $postData = json_decode($json, true);
            try {
                $wasteRequestEntity = $em->getRepository(WasteRequest::class)->findOneBy([
                    "requestUuid" => $postData["wuid"]
                ]);
                if ($wasteRequestEntity == NULL) {
                    throw new \Exception("Waste Request is required");
                }

                $activeUser = $this->apiAuth->getContainerIdentity();

                $identity = $em->getRepository(User::class)->findOneBy([
                    "uuid" => $activeUser["uuid"]
                ]);

                $data["for"] = $wasteRequestEntity->getUser()->getId();
                $data["by"] =  $identity->getId();
                $data["to"] = $identity->getId();
                $data["wid"] = $wasteRequestEntity->getId();


                $this->customerService->generateCustomerHandshakeEntity($data);

                $response->setStatusCode(201);
                $jsonModel->setVariables([
                    "success" => true,
                ]);
            } catch (\Throwable $th) {
                $response->setStatusCode(400);
                $jsonModel->setVariables([
                    "success" => false,
                    "decription" => $th->getMessage()
                ]);
            }
        }
        return $jsonModel;
    }



    public function resendPinAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $em = $this->entityManager;
        $response = $this->getResponse();
        if ($request->isPost()) {
            $json = $request->getContent();
            $postData = json_decode($json, true);
            try {
                $wasteRequestEntity = $em->getRepository(WasteRequest::class)->findOneBy([
                    "requestUuid" => $postData["wuid"]
                ]);
                // $allHandShake = $em->getRepository(Han)
                if ($wasteRequestEntity == NULL) {
                    throw new \Exception("Waste Request is required");
                }

                $activeUser = $this->apiAuth->getContainerIdentity();

                $identity = $em->getRepository(User::class)->findOneBy([
                    "uuid" => $activeUser["uuid"]
                ]);

                $data["for"] = $wasteRequestEntity->getUser()->getId();
                $data["to"] = $identity->getId();
                $data["wid"] = $wasteRequestEntity->getId();


                $this->customerService->generateCustomerHandshakeEntity($data);

                $response->setStatusCode(201);
                $jsonModel->setVariables([
                    "success" => true,
                ]);
            } catch (\Throwable $th) {
                $response->setStatusCode(400);
                $jsonModel->setVariables([
                    "success" => false,
                    "decription" => $th->getMessage()
                ]);
            }
        }
        return $jsonModel;
    }

    /**
     * Used to confirm transaction request and confirm pin provided by the customer or dori host
     * @OA\POST( path="/request/api/confirm-pin", tags={"MY REQUEST", "DORIHOST", "TrashBuster"}, description="Used to finalize processing of a request and confirm the pin ",
     *
     * @OA\RequestBody(
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(required={"wuid", "pin_code", "w_weight"},
     * @OA\Property(property="wuid", type="string", example="wer67-hjurkiu7jj-h234rt", description="This is the waste request uuid"),
     * @OA\Property(property="pin_code", type="string", example="2312", description="The pin provided by the customer or dori host"),
     * @OA\Property(property="w_weight", type="integer", example="2312", description="The pin provided by the customer or dori host"),
     * @OA\Property(property="collection_type", type="string", example="2312", description="The pin provided by the customer or dori host")
     * )
     * ),
     * ),
     * @OA\Response(response="201", description="Success"),
     * @OA\Response(response="400", description="Bad Request"),
     * @OA\Response(response="401", description="Not Authorized"),
     * @OA\Response(response="403", description="Not permitted"),
     *
     * security={{"bearerAuth":{}}}
     *
     * )
     *
     * requires
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function confirmPinAction()
    {
        $em = $this->entityManager;
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();
        if ($request->isPost()) {

            try {
                $json = $request->getContent();
                $postData = json_decode($json, true);
                $wasteCollectionType = !isset($postData["collection_type"]) ? GeneralService::WASTE_COLLECTION_TYPE_IRECYCLE : GeneralService::WASTE_COLLECTION_TYPE_WPI;
                if (!isset($postData["wuid"])) {
                    throw new \Exception("Waste uuid  cannot be empty");
                } else if (!isset($postData["pin_code"])) {
                    throw new \Exception("Pin code  cannot be empty");
                } else if (!isset($postData["w_weight"])) {
                    throw new \Exception("The actual weight is required");
                } else {
                    $activeUser = $this->apiAuth->getContainerIdentity();



                    $identity = $em->getRepository(User::class)->findOneBy([
                        "uuid" => $activeUser["uuid"]
                    ]);
                    $wuid = $postData["wuid"];
                    $w_weight = $postData["w_weight"];
                    /**
                     * @var WasteRequest
                     *
                     */
                    $wasteRequestEntity = $em->getRepository(WasteRequest::class)->findOneBy([
                        "requestUuid" => $wuid
                    ]);

                    /**
                     * @var HandshakePincode
                     */
                    $handshakeCode = $em->getRepository(HandshakePincode::class)->findOneBy([
                        "wasteRequest" => $wasteRequestEntity->getId(),
                        "pinCode" => $postData["pin_code"],
                        "isUsed" => FALSE
                    ]);

                    if ($handshakeCode == NULL) {
                        throw new \Exception("Pin Code is invalid");
                    }


                    if ($wasteRequestEntity == null) {
                        throw new \Exception("This request does not exist");
                    }
                    // Get if pincode exist with waste id 
                    $wasteRequestEntity->setUpdatedOn(new \Datetime())
                        ->setWasteWeigth($w_weight)
                        ->setWasteCollectionType($em->find(WasteCollectionType::class, $wasteCollectionType))
                        ->setHandshakeCode(CustomerService::generateHandshakeCode());

                    $customerUserId =  $wasteRequestEntity->getUser()->getId();
                    $cata["userId"] =  $customerUserId;




                    $wasteRequestEntity->setIsConfirmedHandshake(true)
                        // ->setUpdatedOn(new \Datetime())
                        ->setWasteRequestState($em->find(WasteRequestState::class, GeneralService::WASTE_REQUEST_STATE_COMPLETED));
                    $customerUserEntity = $em->find(User::class, $customerUserId);
                    $wasteRequestActivityEntity = new WasteRequestActivity();
                    $wasteRequestActivityEntity->setCreatedOn(new \DateTime())
                        ->setInititor($customerUserEntity)
                        ->setActivity($em->find(WasteRequestActivityConst::class, GeneralService::REQUEST_ACTIVITY_HANDSHAKE_CONFIMRED))
                        ->setDescription("{$customerUserEntity->getFullname()} has accepted handshake")
                        ->setWasteRequest($wasteRequestEntity);


                    /**
                     * @var WalletApiService
                     */
                    $walletApiService = $this->walletApiService;
                    $shakeData["weight"] = $w_weight;
                    // wasteRequestEntity->getWasteType()->getKgPrice();

                    /**
                     * @var CollectionTypeWasteType
                     */
                    $wasteCollectionTypeWasteTypeEntity = $em->getRepository(CollectionTypeWasteType::class)->findOneBy([
                        "collectionType" => $wasteCollectionType,
                        "wasteType" => $wasteRequestEntity->getWasteType()->getId()
                    ]);
                    $shakeData["kg_price"] = $wasteCollectionTypeWasteTypeEntity->getPricePerUnit();


                    $cata["credit"] = CustomerService::calculateCustomerCredit($shakeData);
                    // $data[""]
                    // $walletApiService->creditWallet($cata);
                    // calculate Credit unit for customer
                    // Send Emails of Credit to customer

                    if ($wasteRequestEntity->getRequestType()->getId() == CustomerService::WASTE_REQUEST_TYPE_PICKUP) {
                        // var_dump("One");
                        /**
                         * @var  AssignedRequest
                         */
                        $assignedRequestEntity = $em->getRepository(AssignedRequest::class)->findOneBy([
                            "wasteRequest" => $wasteRequestEntity->getId(),
                            // "assignedTo" => $postData["request_intiator"]
                        ]);
                        // var_dump($assignedRequestEntity);
                        if ($assignedRequestEntity != NULL) {
                            // var_dump("ONe Inner");
                            $assignedRequestEntity->setAsignedRequestStatus($em->find(AssignedRequestStatus::class, TrashBustterService::ASSIGNED_REQUEST_STATUS_COMPLETED))
                                ->setCompletedOn(new \DateTime())
                                ->setIsCompleted(TRUE)
                                ->setUpdatedOn(new \Datetime());

                            $em->persist($assignedRequestEntity);
                        }
                    }

                    //set Is Used to true
                    $handshakeCode->setIsUsed(TRUE)->setUsedOn(new \DateTime());

                    // $em->persist($wasteRequestActivityEntity);
                    $em->persist($wasteRequestEntity);
                    $em->persist($handshakeCode);
                    $this->walletApiService->creditUserWallet($cata);
                    $em->flush();

                    $jsonModel->setVariables([
                        "data" => [
                            "success" => true,
                            "customer_name" => $wasteRequestEntity->getUser()->getFullname(),
                            "waste_type" => $wasteRequestEntity->getWasteType()->getType(),
                            "customer_type" => $wasteRequestEntity->getWasteCollectionType()->getType(),
                            // "scale"=>
                            "credit" => $cata["credit"]
                        ]
                    ]);

                    // $em->persist($wasteRequestEntity);
                    // // finaize on funding customer wallet

                    // $em->flush();



                    // $this->pusherObject->getPusherObject()->trigger($this->settings->getPusherChannel(), $wasteRequestEntity->getRequestUuid(), $pusherData);
                    $response->setStatusCode(201);
                    $jsonModel->setVariables([
                        "success" => true,
                        "description" => "Retrieved data"
                    ]);
                }
            } catch (\Throwable $th) {
                $response->setStatusCode(400);
                $jsonModel->setVariables([
                    "success" => false,
                    "decription" => $th->getMessage()
                ]);
            }

            // Pusher subscription


        }
        return $jsonModel;
    }

    /**
     *
     * 
     *
     * @OA\GET( path="/request/api/view-pin-list", tags={"MY REQUEST"},
     *
     * @OA\Response(response="200", description="Success",
     *  content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                       
     *                     ),
     *
     *
     *                     example={
     * "data": {{
     * "id": 2,
     * 
     * "trashbuster": "Buster One Name",
     * "buster_number": "01023426457",
     *"waste_uuid": "f62b822b-18a6-4195-ae4a-2b9f6215f531",
     *"quantity": 0,
     *
     * 
     *}
     * }
     *}
     *
     *
     *
     *
     *                 )
     *             )
     *         } ),
     * @OA\Response(response="400", description="Bad Request"),
     * @OA\Response(response="401", description="Not Authorized"),
     * @OA\Response(response="403", description="Not permitted"),
     *
     * security={{"bearerAuth":{}}}
     *
     *
     * )
     *
     * 
     *
     */
    public function viewPinListAction()
    {
        $jsonModel = new JsonModel();
        $em = $this->entityManager;
        $activeUser = $this->apiAuth->getContainerIdentity();

        $identity = $em->getRepository(User::class)->findOneBy([
            "uuid" => $activeUser["uuid"]
        ]);
        $data = $em->getRepository(HandshakePincode::class)->createQueryBuilder("h")
            ->select("h.id as id, gb.fullname as trashbuster, gb.username as buster_number,  w.requestUuid as waste_uuid")
            ->leftJoin("h.generatedFor", "gf")
            ->leftJoin("h.generatedBy", "gb")
            ->leftJoin("h.wasteRequest", "w")

            ->andWhere("gf.id = :gfid")
            ->andWhere("h.isUsed = :isUsed")
            ->setParameters([
                "gfid" => $identity->getId(),
                "isUsed" => FALSE
            ])
            ->orderBy("h.id", "DESC")
            ->getQuery()
            ->getArrayResult();
        $jsonModel->setVariables([
            "data" => $data
        ]);
        return $jsonModel;
    }

    /**
     *
     * @OA\Parameter(
     *   parameter="wuuid_query",
     *   name="wasteUuid",
     *   description="Waste unique Identity ",
     *  example="jurt574-ujtuuyi-578mnhfytr-ouutj",
     *   @OA\Schema(
     *     type="string"
     *   ),
     *   in="path",
     *   required=true
     * )
     *
     * @OA\GET( path="/request/api/view-pin/{wasteUuid}", tags={"MY REQUEST"}, @OA\Parameter(ref="#/components/parameters/wuuid_query"),
     *
     * @OA\Response(response="200", description="Success",
     *  content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                       
     *                     ),
     *
     *
     *                     example={
     * "data": {
     * "id": 2,
     *  "pincode": 3412,
     * "trashbuster": "Buster One Name ",
     * "buster_number": "01023426457",
     *"waste_uuid": "f62b822b-18a6-4195-ae4a-2b9f6215f531",
     *"quantity": 0,
     *
     * 
     *}
     *}
     *
     *
     *
     *
     *                 )
     *             )
     *         } ),
     * @OA\Response(response="400", description="Bad Request"),
     * @OA\Response(response="401", description="Not Authorized"),
     * @OA\Response(response="403", description="Not permitted"),
     *
     * security={{"bearerAuth":{}}}
     *
     *
     * )
     *
     * requires
     *
     *
     */
    public function viewPinAction()
    {
        $jsonModel = new JsonModel();
        $customerService = $this->customerService;
        $response = $this->getResponse();
        $activeUser = $this->apiAuth->getContainerIdentity();
        $wuid = $this->params()->fromRoute("id", null);
        $em = $this->entityManager;
        try {
            if ($wuid == null) {
                throw new \Exception("Required ID is empty");
            }
            $identity = $em->getRepository(User::class)->findOneBy([
                "uuid" => $activeUser["uuid"]
            ]);
            $data["wuid"] = $wuid;
            $data["user_id"] = $identity->getId();

            $jsonModel->setVariables([
                "data" => $customerService->getCustomerHandshakeEntity($data)[0]
            ]);
        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "decription" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }





    /**
     * Set undocumented variable
     *
     * @param  ApiAuthenticateService  $apiAuth  Undocumented variable
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

    /**
     * Set undocumented variable
     *
     * @param  CustomerService  $customerService  Undocumented variable
     *
     * @return  self
     */
    public function setCustomerService(CustomerService $customerService)
    {
        $this->customerService = $customerService;

        return $this;
    }
}
