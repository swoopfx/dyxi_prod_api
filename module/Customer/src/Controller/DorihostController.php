<?php

namespace Customer\Controller;

use Actors\Entity\AssignedRequest;
use Actors\Entity\AssignedRequestStatus;
use Actors\Entity\PostedWaste;
use Actors\Entity\UserBankAccount;
use Actors\Entity\WasteCollection;
use Actors\Service\TrashBustterService;
use Authentication\Entity\Roles;
use Authentication\Entity\User;
use Authentication\Entity\UserState;
use Customer\Entity\WasteRequest;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Laminas\InputFilter\InputFilter;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Authentication\Service\ApiAuthenticateService;
use Authentication\Service\AuthenticationService;
use Authentication\Service\RegisterService;
use Customer\Entity\Customer;
use Customer\Entity\WasteRequestActivity;
use Customer\Service\CustomerService;
use Customer\Service\DoriHostService;
use Doctrine\ORM\Tools\Pagination\Paginator;
use DoctrineModule\Validator\NoObjectExists;
use General\Entity\EstimatedWeight;
use General\Entity\PostWasteStatus;
use General\Entity\WasteCollectionType;
use General\Entity\WasteRequestActivityConst;
use General\Entity\WasteRequestState;
use General\Entity\WasteRequestType;
use General\Entity\WasteType;
use General\Service\GeneralService;
use Laminas\Validator\StringLength;
use Ramsey\Uuid\Uuid;
use Wallet\Service\WalletApiService;
use Wallet\Service\WalletService;
use General\Entity\Banks;

class DorihostController extends AbstractActionController
{
    /**
     * Undocumented variable
     *
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Undocumented variable
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

    /**
     * Undocumented variable
     *
     * @var DoriHostService
     */
    private $dorihostService;

    /**
     * Undocumented variable
     *
     * @var WalletService
     */
    private $walletService;

    /**
     * gets all dropoff initiated by customer
     *
     * @OA\GET( path="/dori/api/get-all-dropoff", tags={"DORIHOST"}, description="Gets all dropp of initiated by customer",
     *  @OA\Response(response="201", description="Created",
     *  content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="success",
     *                         type="boolean",
     *                         description="Defines the state of the request"
     *                     ),
     *
     *
     *                     example={
     *   {
     *"previous_page": 1,
     *"next_page": 1,
     * "data": {
     * {
     *  "id": 16,
     * "requestUuid": "32dc4095-78a4-4e23-9bc6-a7e2a19329cb",
     * "isActive": true,
     * "user": {
     *  "id": 13,
     *  "fullname": "Idowu Yusuf Chukwuma",
     *  "username": "09012121212",
     *  "email": "ezekiel_a@yahoo.com",
     *  "emailConfirmed": true,
     *  "uid": "resu64c535b72ae63",
     *  "uuid": "f7619656-39a2-4972-8fd8-41b6061bb272"
     *},
     * "estimatedWeight": {
     *  "id": 100,
     *  "weight": "< 50kg"
     *},
     *"requestType": {
     *  "id": 200,
     *  "type": "Drop Off"
     *},
     *"wasteType": {
     *  "id": 100,
     *  "type": "Plastic"
     *},
     *"wasteRequestState": {
     *  "id": 100,
     *  "state": "INITIATED"
     *},
     *"dropOffhost": {
     *  "id": 26,
     *  "fullname": "Latest Dori",
     *  "email": "19@gmail.com",
     *  "uid": "resu64e0ad504d4b7",
     *  "uuid": "0c65e841-e104-4417-87e9-898c421ac178"
     *}
     *},
     *{
     *"id": 15,
     *"requestUuid": "434f1597-b4ba-4252-ab5f-3f938848457b",
     *"isActive": true,
     *"user": {
     *  "id": 13,
     *  "fullname": "Idowu Yusuf Chukwuma",
     *  "username": "09012121212",
     *  "email": "ezekiel_a@yahoo.com",
     *  "emailConfirmed": true,
     *  "uid": "resu64c535b72ae63",
     *  "uuid": "f7619656-39a2-4972-8fd8-41b6061bb272"
     * },
     *"estimatedWeight": {
     *  "id": 100,
     *  "weight": "< 50kg"
     *},
     * "requestType": {
     *  "id": 200,
     *  "type": "Drop Off"
     *},
     *"wasteType": {
     *  "id": 100,
     *  "type": "Plastic"
     *},
     *"wasteRequestState": {
     *  "id": 100,
     *  "state": "INITIATED"
     *},
     *"dropOffhost": {
     *  "id": 26,
     *  "fullname": "Latest Dori",
     *  "email": "19@gmail.com",
     *  "uid": "resu64e0ad504d4b7",
     *  "uuid": "0c65e841-e104-4417-87e9-898c421ac178"
     *}
     *}
     *}
     *}
     *     }
     *
     *
     *
     *
     *                 )
     *             )
     *         } ),
     * @OA\Response(response="401", description="Not Authorized"),
     * @OA\Response(response="403", description="Error"),
     * security={{"bearerAuth":{}}}
     * )
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function getAllDropoffAction()
    {


        $jsonModel = new JsonModel();
        $response = $this->getResponse();
        $identity = $this->apiAuth->getContainerIdentity();
        $userEntity = $this->entityManager->getRepository(User::class)->findOneBy([
            "uuid" => $identity["uuid"]
        ]);
        try {
            $order = ($this->params()->fromQuery("order", NULL) == null ? "DESC" : "ASC");
            $pageCount = ($this->params()->fromQuery("page_count", 40) > 100 ? 100 : $this->params()->fromQuery("page_count", 40));
            $orderBy = $this->params()->fromQuery("order_by", "id");
            $query = $this->entityManager->createQueryBuilder()->select([
                "partial c.{id, requestUuid, estimatedWeight, wasteType, requestType, wasteRequestState, isActive, dropOffhost}",   
                "partial u.{id, fullname, username, email, role, state, emailConfirmed, uuid, uid}",
                "partial ew.{id, weight}",
                "partial wt.{id, type}",
                "partial wrs.{id, state}",
                "partial doh.{id, fullname, uuid, email, uid}",
                "partial rt.{id, type}",
                // "partial wu.{id, fullname, username, email}"
            ])->from(WasteRequest::class, "c")
                ->leftJoin("c.user", "u")
                ->leftJoin("c.estimatedWeight", "ew")
                // ->leftJoin("c.wasteRequest", "w")
                ->leftJoin("c.requestType", "rt")
                ->leftJoin("c.wasteType", "wt")
                ->leftJoin("c.wasteRequestState", "wrs")
                ->leftJoin("c.dropOffhost", "doh")
                ->where("doh.id = :userId")
                ->andWhere("c.isActive = :active")
                ->andWhere("rt.id = :rt")
                ->setParameters([
                    "userId" => $userEntity->getId(),
                    "active" => TRUE,
                    "rt" => CustomerService::WASTE_REQUEST_TYPE_DROPOFF
                ])
                ->orderBy("c.{$orderBy}", $order)
                ->getQuery()
                ->setHydrationMode(Query::HYDRATE_ARRAY);

            $paginator = new Paginator($query);
            $totalItems = count($paginator);

            $currentPage = ($this->params()->fromQuery("page")) ?: 1;
            $totalPageCount = ceil($totalItems / $pageCount);
            $nextPage = (($currentPage < $totalPageCount) ? $currentPage + 1 : $totalPageCount);
            $previousPage = (($currentPage > 1) ? $currentPage - 1 : 1);

            $records = $paginator->getQuery()->setFirstResult($pageCount * ($currentPage - 1))
                // ->setMaxResults($pageCount)
                ->getResult(Query::HYDRATE_ARRAY);

            $jsonModel->setVariables([
                "previous_page" => $previousPage,
                "next_page" => $nextPage,
                "data" => $records
            ]);
        } catch (\Throwable $th) {

            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => FALSE,
                "desc" => $th->getMessage()
            ]);
        }
        return $jsonModel;
    }


    /**
     * get all available active waste in the waste request entity
     * Ths Host selects from the list of active waste request and 
     *
     * @OA\GET( path="/dori/api/get-active-waste-request", tags={"DORIHOST"}, description="This is called to list all droppoff request associa",
     * @OA\Response(response="200", description="Success"),
     * @OA\Response(response="401", description="Not Authorized"),
     * @OA\Response(response="403", description="Error"),
     * security={{"bearerAuth":{}}}
     * )
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function getActiveWasteRequestAction()
    {
        $jsonModel = new JsonModel();
        $em = $this->entityManager;
        try {
            $query = $this->entityManager->getRepository(WasteRequest::class)
                ->createQueryBuilder("w")
                ->select(["w", "u", "doh"])
                ->leftJoin("w.user", "u")
                ->leftJoin("w.dropOffhost", "doh")
                ->where("doh.uuid = :host");
            // if(isset("")){

            // }
            $data = $query->getQuery()->getResult(Query::HYDRATE_ARRAY);

            $jsonModel->setVariables([
                "data" => $data
            ]);
        } catch (\Throwable $th) {
            $jsonModel->setVariables([]);
        }
        return $jsonModel;
    }

    /**
     * This is executed by the DORIHOST or trashbuster, as a measure to bypass the triger handshake and accept handshake value chain
     * @OA\POST( path="/dori/api/overwrite-handshake", tags={"DORIHOST", "TrashBuster"}, description="This is executed by the DORIHOST or trashbuster, as a measure to bypass the triger handshake and accept handshake value chain",
     *
     * @OA\RequestBody(
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(required={"wuid", "w_weight"},
     * @OA\Property(property="wuid", type="string", example="wer67-hjurkiu7jj-hyyyt", description="This is the waste request uuid"),
     * @OA\Property(property="w_weight", type="integer", example="23", description="This is the weigth in kilograms of the waste the customer has brough to the DORI HOST"),
     * )
     * ),
     * ),
     *  @OA\Response(response="201", description="Created",
     *  content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="success",
     *                         type="boolean",
     *                         description="Defines the state of the request"
     *                     ),
     *
     *
     *                     example={
     *      {
     * "success": true,
     * "customer_fullname": "Idowu Yusuf Chukwuma",
     * "credit": 230
     *}
     * 
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
     * )
     *
     * requires
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function overwriteHandshakeAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getREsponse();
        $em = $this->entityManager;
        try {
            if ($request->isPost()) {
                $json = $request->getContent();
                $postData = json_decode($json, true);
                if (!isset($postData["wuid"]) || !isset($postData["w_weight"])) {
                    throw new \Exception("Waste uuid or Waste weight cannot be empty");
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

                    $userEntity = $wasteRequestEntity->getUser();

                    if ($wasteRequestEntity == null) {
                        throw new \Exception("This request does not exist");
                    }
                    $wasteRequestEntity->setUpdatedOn(new \Datetime())
                        ->setWasteWeigth($w_weight)
                        ->setHandshakeCode(CustomerService::generateHandshakeCode())
                        ->setIsConfirmedHandshake(true)
                        ->setWasteRequestState($em->find(WasteRequestState::class, GeneralService::WASTE_REQUEST_STATE_COMPLETED));


                    $wasteRequestActivityEntity = new WasteRequestActivity();
                    $wasteRequestActivityEntity->setCreatedOn(new \DateTime())
                        ->setInititor($userEntity)
                        ->setActivity($em->find(WasteRequestActivityConst::class, GeneralService::REQUEST_ACTIVITY_HANDSHAKE_CONFIMRED))
                        ->setDescription("{$userEntity->getFullname()} has accepted handshake and transaction completed")
                        ->setWasteRequest($wasteRequestEntity);

                    $walletService = $this->walletService;
                    $walletService->setUserUuid($userEntity->getUuid());


                    $shakeData["weight"] = $wasteRequestEntity->getWasteWeigth();
                    $shakeData["kg_price"] = $wasteRequestEntity->getWasteType()->getKgPrice();

                    $credit = CustomerService::calculateCustomerCredit($shakeData);
                    $walletService->creditWallet($credit);

                    if ($identity->getRole()->getId() == AuthenticationService::USER_ROLE_TRASHBUSTER) {

                        /**
                         * @var  AssignedRequest
                         */
                        $assignedRequestEntity = $em->getRepository(AssignedRequest::class)->findOneBy([
                            "wasteRequest" => $wasteRequestEntity->getId(),
                            "assignedTo" => $identity->getId()
                        ]);

                        $assignedRequestEntity->setAsignedRequestStatus($em->find(AssignedRequestStatus::class, TrashBustterService::ASSIGNED_REQUEST_STATUS_COMPLETED))
                            ->setUpdatedOn(new \Datetime());

                        $em->persist($assignedRequestEntity);
                    }


                    $em->persist($wasteRequestActivityEntity);

                    $em->persist($wasteRequestEntity);
                    $em->flush();

                    // $pusherData["handshake_code"] = $wasteRequestEntity->getHandshakeCode();
                    // $pusherData["waste_weight"] = $w_weight;
                    // $pusherData["request_uuid"] = $wasteRequestEntity->getRequestUuid();

                    // $this->pusherObject->getPusherObject()->trigger($this->settings->getPusherChannel(), $wasteRequestEntity->getRequestUuid(), $pusherData);
                    $response->setStatusCode(201);
                    $jsonModel->setVariables([
                        "success" => true,
                        "customer_fullname" => $wasteRequestEntity->getUser()->getFullname(),
                        "credit" => $credit
                    ]);

                    // Pusher subscription
                }
            }
        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "decription" => $th->getMessage()
            ]);
        }
        // if($$)
        return $jsonModel;
    }


    /**
     * This is used to search for customers by buster or dorihost
     * @OA\POST( path="/dori/api/waste-collection-search-customers", tags={"DORIHOST", "TrashBuster"}, description="Used to make sure a dori host also gatheres waste by self submitting ",
     *
     * @OA\RequestBody(
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(required={"keyword"},
     *
     * @OA\Property(property="keyword", type="string", example="abc@email.com", description="This could be email, phone number or the name of the customer being serched for ")
     *
     * )
     * ),
     * ),
     * @OA\Response(response="200", description="Success",
     *  content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="success",
     *                         type="boolean",
     *                         description="Defines the state of the request"
     *                     ),
     *
     *
     *                     example={
     *  
     *  "data": {
     * {
     *  "id": 13,
     * "fullname": "Idowu Yusuf Chukwuma",
     * "email": "ezekiel_a@yahoo.com",
     * "uid": "resu64c535b72ae63",
     * "uuid": "f7619656-39a2-4972-8fd8-41b6061bb272",
     * "role": {
     *  "id": 100,
     *  "name": "Customer"
     *  }
     * },
     * {
     *"id": 14,
     * "fullname": "Idowu Yusuf Chukwuma",
     *"email": "swoopfx@yahoo.com",
     *"uid": "resu64c53db5a18e4",
     *"uuid": "8a56b2d5-858b-49eb-bebc-00360775e619",
     * "role": {
     *  "id": 100,
     *  "name": "Customer"
     * }
     * }
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
     * )
     *
     * requires
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function wasteCollectionSearchCustomersAction()
    {
        $em = $this->entityManager;
        $jsonModel = new jsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();
        if ($request->isPost()) {
            // var_dump($post)
            try {
                $json = $request->getContent();
                $post = json_decode($json, true);
                if ($post == NULL) {
                    throw new \Exception("Keyword is required");
                }
                $qb = $em->createQueryBuilder();
                $keyword = $post["keyword"];
                // $em->createQuery('SELECT t FROM Authentication\Entity\User t (WHERE t.email AND WHERE  LIKE :title'));
                $data = $qb->select(["partial tag.{id, uuid, email, fullname, uid}", "partial r.{id, name}"])
                    ->from(User::class, 'tag')
                    ->leftJoin("tag.role", "r")
                    ->where($qb->expr()->orX(
                        $qb->expr()->like('tag.email', ':title'),
                        $qb->expr()->like('tag.username', ':title'),
                        $qb->expr()->like('tag.fullname', ':title')
                    ))
                    ->andWhere("r.id = :role")
                    ->setParameters([
                        'title' => '%' . $keyword . '%',
                        "role" => AuthenticationService::USER_ROLE_CUSTOMER
                    ])->getQuery()->getArrayResult();
                $jsonModel->setVariables([
                    "data" => $data
                ]);
                //$qb->expr()->like('tag.email', ':title')
                $response->setStatusCode(200);
            } catch (\Throwable $th) {
                $jsonModel->setVariables([
                    "success" => false,
                    "desc" => $th->getMessage(),
                ]);
                $response->setStatusCode(400);
            }
        }

        return $jsonModel;
    }


    /**
     * Used to get detail/Information for waste Collection 
     * @OA\POST( path="/dori/api/waste-collection-init", tags={"DORIHOST", "TrashBuster"}, description="Used to get detail/Information for waste Collection",
     *
     * @OA\RequestBody(
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(required={"waste_collection_type", "waste_type", "unit_value", "user_uuid"},
     *
     * @OA\Property(property="waste_collection_type", type="integer", example="200", description="ID of type of waste collections being submitted, reference the getWasteCollectionType endpointon the  general  Tabfor reference"),
     * @OA\Property(property="waste_type", type="integer", example="200", description="ID of type of waste being submitted, reference the getWasteCollectionType endpointon the  general  Tabfor reference"),
     * @OA\Property(property="unit_value", type="integer", example="12", description="Ths is either the unnit of countable bittles or weight in kg of the waste collected"),
     * @OA\Property(property="user_uuid", type="string", example="685kiifuty-e44598-iu877987", description="This is the unique indentifier of the user, this is retrived from the waste-collection-search-customers endpoint"),
     *
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
    public function wasteCollectionInitAction()
    {
        $jsonModel = new JsonModel();
        $response = $this->getResponse();
        $em = $this->entityManager;
        // $activeUser = $this->apiAuth->getContainerIdentity();

        $request = $this->getRequest();
        if ($request->isPost()) {
            $json = $request->getContent();
            $postData = json_decode($json, true);
            $inputFilter = new InputFilter();
            $inputFilter->add([
                'name' => 'waste_collection_type',
                'required' => true,
                'filters' => [
                    [
                        'name' => 'StripTags'
                    ],
                    [
                        'name' => 'StringTrim'
                    ]
                ],
                'validators' => [
                    [
                        'name' => 'NotEmpty',
                        'options' => [
                            'messages' => [
                                'isEmpty' => 'Waste Collection Type  is requuired'
                            ]
                        ]
                    ]
                ]
            ]);

            $inputFilter->add([
                'name' => 'waste_type',
                'required' => true,
                'filters' => [
                    [
                        'name' => 'StripTags'
                    ],
                    [
                        'name' => 'StringTrim'
                    ]
                ],
                'validators' => [
                    [
                        'name' => 'NotEmpty',
                        'options' => [
                            'messages' => [
                                'isEmpty' => 'Waste  Type  is requuired'
                            ]
                        ]
                    ]
                ]
            ]);

            $inputFilter->add([
                'name' => 'unit_value',
                'required' => true,
                'filters' => [
                    [
                        'name' => 'StripTags'
                    ],
                    [
                        'name' => 'StringTrim'
                    ]
                ],
                'validators' => [
                    [
                        'name' => 'NotEmpty',
                        'options' => [
                            'messages' => [
                                'isEmpty' => 'Unit Value is requuired'
                            ]
                        ]
                    ]
                ]
            ]);

            $inputFilter->add([
                'name' => 'user_uuid',
                'required' => true,
                'filters' => [
                    [
                        'name' => 'StripTags'
                    ],
                    [
                        'name' => 'StringTrim'
                    ]
                ],
                'validators' => [
                    [
                        'name' => 'NotEmpty',
                        'options' => [
                            'messages' => [
                                'isEmpty' => 'User UUid is requuired'
                            ]
                        ]
                    ]
                ]
            ]);

            $inputFilter->setData($postData);
            if ($inputFilter->isValid()) {
                try {
                    $data = $inputFilter->getValues();

                    $userEntity = $em->getRepository(User::class)->findOneBy([
                        "uuid" => $data["user_uuid"]
                    ]);

                    $cData = $this->dorihostService->calculateWasteCollection($data);

                    $jsonModel->setVariables([
                        "credits" => $cData["credit"],
                        "fullname" => $userEntity->getFullname(),
                        "email" => $userEntity->getEmail(),
                        "waste_type" => $cData["waste_type"],
                        "unit" => intval($data["unit_value"])
                    ]);
                } catch (\Throwable $th) {
                    //throw $th;
                    $jsonModel->setVariables([
                        "success" => false,
                        "message" => $th->getMessage(),
                        "desc" => $th->getTrace(),

                    ]);
                    $response->setStatusCode(400);
                }
            }
        }

        return $jsonModel;
    }


    /**
     * Used to hydrate detail/Information for waste Collection 
     * @OA\POST( path="/dori/api/waste-collection-finalization", tags={"DORIHOST", "TrashBuster"}, description="Used to hydrate detail/Information for waste Collection",
     *
     * @OA\RequestBody(
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(required={"waste_collection_type", "waste_type", "unit_value", "user_uuid"},
     *
     * @OA\Property(property="waste_collection_type", type="integer", example="100", description="ID of type of waste collections being submitted, reference the getWasteCollectionType endpointon the  general  Tabfor reference"),
     * @OA\Property(property="waste_type", type="integer", example="200", description="ID of type of waste being submitted, reference the getWasteCollectionType endpointon the  general  Tabfor reference"),
     * @OA\Property(property="unit_value", type="integer", example="12", description="Ths is either the unnit of countable bittles or weight in kg of the waste collected"),
     * @OA\Property(property="user_uuid", type="string", example="685kiifuty-e44598-iu877987", description="This is the unique indentifier of the user, this is retrived from the waste-collection-search-customers endpoint"),
     *
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
    public function wasteCollectionFinalizationAction()
    {
        $jsonModel = new JsonModel();
        $response = $this->getResponse();
        $em = $this->entityManager;
        // $activeUser = $this->apiAuth->getContainerIdentity();

        $request = $this->getRequest();
        if ($request->isPost()) {
            $json = $request->getContent();
            $postData = json_decode($json, true);
            $inputFilter = new InputFilter();
            $inputFilter->add([
                'name' => 'waste_collection_type',
                'required' => true,
                'filters' => [
                    [
                        'name' => 'StripTags'
                    ],
                    [
                        'name' => 'StringTrim'
                    ]
                ],
                'validators' => [
                    [
                        'name' => 'NotEmpty',
                        'options' => [
                            'messages' => [
                                'isEmpty' => 'Waste Collection Type  is requuired'
                            ]
                        ]
                    ]
                ]
            ]);

            $inputFilter->add([
                'name' => 'waste_type',
                'required' => true,
                'filters' => [
                    [
                        'name' => 'StripTags'
                    ],
                    [
                        'name' => 'StringTrim'
                    ]
                ],
                'validators' => [
                    [
                        'name' => 'NotEmpty',
                        'options' => [
                            'messages' => [
                                'isEmpty' => 'Waste  Type  is requuired'
                            ]
                        ]
                    ]
                ]
            ]);

            $inputFilter->add([
                'name' => 'unit_value',
                'required' => true,
                'filters' => [
                    [
                        'name' => 'StripTags'
                    ],
                    [
                        'name' => 'StringTrim'
                    ]
                ],
                'validators' => [
                    [
                        'name' => 'NotEmpty',
                        'options' => [
                            'messages' => [
                                'isEmpty' => 'Unit Value is requuired'
                            ]
                        ]
                    ]
                ]
            ]);

            $inputFilter->add([
                'name' => 'user_uuid',
                'required' => true,
                'filters' => [
                    [
                        'name' => 'StripTags'
                    ],
                    [
                        'name' => 'StringTrim'
                    ]
                ],
                'validators' => [
                    [
                        'name' => 'NotEmpty',
                        'options' => [
                            'messages' => [
                                'isEmpty' => 'User UUid is requuired'
                            ]
                        ]
                    ]
                ]
            ]);

            $inputFilter->setData($postData);
            if ($inputFilter->isValid()) {
                try {
                    $data = $inputFilter->getValues();

                    /**
                     * @var User
                     */
                    $userEntity = $em->getRepository(User::class)->findOneBy([
                        "uuid" => $data["user_uuid"]
                    ]);

                    $activeUser = $this->apiAuth->getContainerIdentity();
                    $identity = $em->getRepository(User::class)->findOneBy([
                        "uuid" => $activeUser["uuid"]
                    ]);

                    $cData = $this->dorihostService->calculateWasteCollection($data);

                    $wasteCollectionEntity = new WasteCollection();

                    $wasteCollectionEntity->setCreatedOn(new \DateTime())
                        ->setUser($userEntity)
                        ->setValuess($data["unit_value"])
                        ->setCollectionUuid(Uuid::uuid4())
                        ->setIsActive(TRUE)
                        ->setCollectionType($em->find(WasteCollectionType::class, $data["waste_collection_type"]));


                    $walletService = $this->walletService->setUserUuid($data["user_uuid"]);
                    $walletService->creditWallet($cData["credit"]);

                    if ($identity->getRole()->getId() == AuthenticationService::USER_ROLE_TRASHBUSTER && $data["waste_collection_type"] == 100) {
                        $wasteRequestEntity = new WasteRequest();
                        $wasteRequestEntity->setCreatedOn(new \Datetime())->setRequestUuid(Uuid::uuid4())
                            ->setRequestType($em->find(WasteRequestType::class, CustomerService::WASTE_REQUEST_TYPE_PICKUP))
                            ->setWasteType($em->find(WasteType::class, $data["waste_type"]))
                            ->setUser($userEntity)
                            ->setRequestId(CustomerService::generateRequestId())
                            ->setHandshakeCode(Uuid::uuid4())
                            ->setWasteRequestState($em->find(WasteRequestState::class, GeneralService::WASTE_REQUEST_STATE_COMPLETED))
                            ->setWasteWeigth($data["unit_value"]);

                        $em->persist($wasteRequestEntity);
                    }
                    $em->persist($wasteCollectionEntity);
                    $em->flush();



                    //if successfull hydrate wallet with units

                    $jsonModel->setVariables([
                        "credits" => $cData["credit"],
                        "fullname" => $userEntity->getFullname(),
                        "email" => $userEntity->getEmail(),
                        "waste_type" => $cData["waste_type"],
                        "unit" => intval($data["unit_value"]),
                        "wallet_balance" => $walletService->getBalance()
                    ]);
                } catch (\Throwable $th) {
                    //throw $th;
                    $jsonModel->setVariables([
                        "success" => false,
                        "message" => $th->getMessage(),
                        "desc" => $th->getTrace(),

                    ]);
                    $response->setStatusCode(400);
                }
            }
        }

        return $jsonModel;
    }


    // /**
    //  * Used to trigger a handshake with a customer by a dori host
    //  * The Dori host must have enter the wirght in kg of product
    //  * this value is trigered by the ori hhost and the value is confirmed by the customer
    //  * @OA\POST( path="/dori/api/post-request-pickup", tags={"DORIHOST"}, description="Used to trigger a handshake with a customer by a dori host",
    //  *
    //  * @OA\RequestBody(
    //  * @OA\MediaType(
    //  * mediaType="application/json",
    //  * @OA\Schema(required={"request_list"},
    //  * @OA\Property(property="request_list", type="string", example="[10,45,78,67,78]", description="This is the json encoded list of ID of the waste request, put the ID of the selected waste request in an array/list and convert it to a string such that it can be retrieved via json decode from back end"),
    //  * @OA\Property(property="aggregated_weight", type="string", example="23", description="This is the agregated weight in kg"),
    //  *
    //  * )
    //  * ),
    //  * ),
    //  * @OA\Response(response="200", description="Success"),
    //  * @OA\Response(response="400", description="Bad Request"),
    //  * @OA\Response(response="401", description="Not Authorized"),
    //  * @OA\Response(response="403", description="Not permitted"),
    //  *
    //  *security={{"bearerAuth":{}}}
    //  *
    //  * )
    //  *
    //  * requires
    //  *
    //  * @return \Laminas\View\Model\JsonModel
    //  */
    public function postRequestPickupAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();
        $em = $this->entityManager;
        // $userEntity =
        $activeUser = $this->apiAuth->getContainerIdentity();
        $userEntity = $em->getRepository(User::class)->findOneBy([
            "uuid" => $activeUser["uuid"]
        ]);

        if ($request->isPost()) {
            try {
                $json = $request->getContent();
                $postData = json_decode($json, true);
                $decodedList = json_decode($postData["request_list"]);
                if (count($decodedList) == 0) {
                    throw new \Exception("A list is waste request is required");
                }
                $inputFilter = new inputFilter();
                $inputFilter->add([
                    'name' => 'aggregated_weight',
                    'required' => true,
                    'filters' => [
                        [
                            'name' => 'StripTags'
                        ],
                        [
                            'name' => 'StringTrim'
                        ]
                    ],
                    'validators' => [
                        [
                            'name' => 'NotEmpty',
                            'options' => [
                                'messages' => [
                                    'isEmpty' => 'Aggregated weight is requuired'
                                ]
                            ]
                        ]
                    ]
                ]);

                $inputFilter->setData($postData);
                if ($inputFilter->isValid()) {
                    $summativeWasteWeight = 0;
                    foreach ($decodedList as $wasteId) {
                        /**
                         * @var WasteRequest
                         */
                        $wasteRequestEntity = $em->find(WasteRequest::class, $wasteId);
                        $wasteRequestEntity->setIsActive(false)->setUpdatedOn(new \Datetime());
                        $em->persist($wasteRequestEntity);

                        $summativeWasteWeight += $wasteRequestEntity->getWasteWeigth();
                    }

                    $postWasteEntity = new PostedWaste();
                    $postWasteEntity
                        ->setCreatedOn(new \Datetime())
                        ->setAggregatedWeight($summativeWasteWeight)
                        ->setIsActive(true)
                        ->setIssuer($userEntity)
                        ->setPostWasteStatus($em->find(PostWasteStatus::class, DoriHostService::POST_WASTE_STATUS_INITIATED))
                        ->setPostId(self::generatePostId())
                        ->setPostUuid(self::generatePostUuid());

                    $em->persist($postWasteEntity);

                    $em->flush();
                }
            } catch (\Throwable $th) {
                $jsonModel->setVariables([
                    "success" => false,
                    "description" => $th->getMessage(),
                ]);
            }
        }
    }

    /**
     * A Dori host is also a customer, hence can gather waste and eventually submit it to get points
     * @OA\POST( path="/dori/api/dori-self-waste", tags={"DORIHOST"}, description="Used to make sure a dori host also gatheres waste by self submitting ",
     *
     * @OA\RequestBody(
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(required={"wuid", "waste_type", "waste_weigth"},
     *
     * @OA\Property(property="waste_type", type="integer", example="100", description="ID of type of waste being submitted, reference the relevanct general endpoint for reference"),
     * @OA\Property(property="estimated_weight", type="integer", example="100", description="ID  of the Estimated waste "),
     * @OA\Property(property="waste_weigth", type="integer", example="100", description="Actual weight of the waste being posted this is assumed in kg "),
     * @OA\Property(property="note", type="string", example="This is from me ", description="A brief information for the post"),
     *
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
    public function doriSelfWasteAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();
        $em = $this->entityManager;
        if ($request->isPost()) {
            $json = $request->getContent();
            $postData = json_decode($json, true);
            $inputFilter = new InputFilter();
            $inputFilter->setData($postData);
            if ($inputFilter->isValid()) {
                $data = $inputFilter->getValues();
                // $res = [];
                try {
                    $wasteRequestEntity = new WasteRequest();
                    $wasteRequestEntity->setCreatedOn(new \Datetime())
                        ->setRequestId(CustomerService::generateRequestId())
                        ->setRequestUuid(CustomerService::generateRequestUuid())
                        ->setRequestType($em->find(WasteRequestType::class, CustomerService::WASTE_REQUEST_TYPE_DORI_SELF_SERVICE))
                        ->setWasteType($data["waste_type"])
                        ->setNote($data["note"])
                        ->setEstimatedWeight($em->find(EstimatedWeight::class, $data["estimated_weight"]))
                        ->setWasteWeigth($data["waste_weigth"]);

                    $em->persist($wasteRequestEntity);
                    $em->flush();

                    $walletApiService = $this->walletApiService;
                    $shakeData["weight"] = $wasteRequestEntity->getWasteWeigth();
                    $shakeData["kg_price"] = $wasteRequestEntity->getWasteType()->getKgPrice();

                    $cata["credit"] = CustomerService::calculateCustomerCredit($shakeData);
                    // $data[""]
                    $walletApiService->creditWallet($cata);

                    $jsonModel->setVariables([
                        "data" => ""
                    ]);
                    $response->setStatusCode(201);
                } catch (\Throwable $th) {
                    $jsonModel->setVariables([
                        "success" => false,
                        "description" => $th->getMessage()
                    ]);
                    $response->setStatusCode(400);
                }
            }
        }
        return $jsonModel;
    }


    /**
     *
     * This is used by the trashbuster and dorihost to register a  customer
     *
     * @OA\POST( path="/dori/api/create-customer", tags={"TrashBuster", "DORIHOST"}, description="This is used by the trashbuster and DORI HOST to register a  customer",
     *
     * @OA\RequestBody(
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(required={"phonenumber", "fullname", "address", "address_google_place_id", "address_longitude", "address_latitude"},
     * @OA\Property(property="email", type="string", example="abc@123.com", description="Customer Email"),
     * @OA\Property(property="phonenumber", type="string", example="EjFJbnQnbCBBaXJwb3J0IFJkLCBNYWZvbHVrdSBPc2hvZGksIExhZ29zLCBOaWdlcmlhIi4qLAoUChIJjagx-h6OOxARwyZNkX_GTysSFAoSCZk5KvookjsQEfChu91LMqjX", description="Google Place id (Unique) of the Destination"),
     * @OA\Property(property="fullname", type="string", example="15 Jacob adeleye street"),
     * @OA\Property(property="address", type="string", example="12 Olumowe street Ijebu", description="Google Place id (Unique) of the pickup"),
     * @OA\Property(property="address_google_place_id", type="string", example="erriindhikpsfjuhkjnooifjni3", description="Pick up Address"),
     * @OA\Property(property="address_longitude", type="string", example="3.4556666", description="address up longitude"),
     * @OA\Property(property="address_latitude", type="string", example="1.45322", description="address latitude"),
     *  @OA\Property(property="bank", type="integer", example="20", description="Reference to the Ban Acount List"),
     *  @OA\Property(property="account_number", type="string", example="0018111012", description="The account number of the customer"),
     *  @OA\Property(property="account_name", type="string", example="Segun Chukwu", description="This is the name of the account"),
     * 
     *
     *
     * )
     * ),
     * ),
     * @OA\Response(response="204", description="Success"),
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
    public function createCustomerAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $em = $this->entityManager;
        $response = $this->getResponse();
        if ($request->isPost()) {
            $json = $request->getContent();
            $data = json_decode($json, true);
            try {
                $inputFilter = new InputFilter();

                $inputFilter->add([
                    'name' => 'phonenumber',
                    'required' => true,
                    "allow_empty" => false,
                    'filters' => [
                        [
                            'name' => 'StripTags'
                        ],
                        [
                            'name' => 'StringTrim'
                        ]
                    ],
                    'validators' => [
                        [
                            'name' => 'NotEmpty',
                            'options' => [
                                'messages' => [
                                    'isEmpty' => 'Phone numvber is required'
                                ]
                            ]
                        ],
                        [
                            "name" => NoObjectExists::class,
                            "options" => [
                                "use_context" => true,
                                "object_repository" => $this->entityManager->getRepository(User::class),
                                "objject_manager" => $this->entityManager,
                                "fields" => [
                                    "username"
                                ],
                                "messages" => [
                                    NoObjectExists::ERROR_OBJECT_FOUND => "please use another identity"
                                ]
                            ]
                        ],
                        [
                            'name' => StringLength::class,
                            'options' => [
                                // 'messages' => [],
                                'min' => 6,
                                'max' => 256,
                                'messages' => [
                                    StringLength::TOO_SHORT => 'Try Something more longer',
                                    StringLength::TOO_LONG => 'Could you really remember this long identity'
                                ]
                            ],
                        ]
                    ]
                ]);


                $inputFilter->add([
                    'name' => 'fullname',
                    'required' => true,
                    "allow_empty" => false,
                    'filters' => [
                        [
                            'name' => 'StripTags'
                        ],
                        [
                            'name' => 'StringTrim'
                        ]
                    ],
                    'validators' => [
                        [
                            'name' => 'NotEmpty',
                            'options' => [
                                'messages' => [
                                    'isEmpty' => 'Full Name is required'
                                ]
                            ]
                        ],

                        [
                            'name' => StringLength::class,
                            'options' => [
                                // 'messages' => [],
                                'min' => 6,
                                'max' => 256,
                                'messages' => [
                                    StringLength::TOO_SHORT => 'Try Something more longer',
                                    StringLength::TOO_LONG => 'Could you really remember this long identity'
                                ]
                            ],
                        ]
                    ]
                ]);

                $inputFilter->add([
                    'name' => 'email',
                    'required' => false,
                    "allow_empty" => true,
                    'filters' => [
                        [
                            'name' => 'StripTags'
                        ],
                        [
                            'name' => 'StringTrim'
                        ]
                    ],
                    'validators' => []
                ]);

                $inputFilter->add([
                    'name' => 'address_google_place_id',
                    'required' => false,
                    "allow_empty" => true,
                    'filters' => [
                        [
                            'name' => 'StripTags'
                        ],
                        [
                            'name' => 'StringTrim'
                        ]
                    ],
                    'validators' => [
                        [
                            'name' => 'NotEmpty',
                            'options' => [
                                'messages' => [
                                    'isEmpty' => 'Google place Id is required'
                                ]
                            ]
                        ],

                        [
                            'name' => StringLength::class,
                            'options' => [
                                // 'messages' => [],
                                'min' => 6,
                                'max' => 256,
                                'messages' => [
                                    StringLength::TOO_SHORT => 'Try Something more longer',
                                    StringLength::TOO_LONG => 'Could you really remember this long identity'
                                ]
                            ],
                        ]
                    ]
                ]);

                $inputFilter->add([
                    'name' => 'address_longitude',
                    'required' => false,
                    "allow_empty" => true,
                    'filters' => [
                        [
                            'name' => 'StripTags'
                        ],
                        [
                            'name' => 'StringTrim'
                        ]
                    ],
                    'validators' => [
                        [
                            'name' => 'NotEmpty',
                            'options' => [
                                'messages' => [
                                    'isEmpty' => 'Address Longitude is required'
                                ]
                            ]
                        ],

                        [
                            'name' => StringLength::class,
                            'options' => [
                                'messages' => [],
                                'min' => 6,
                                'max' => 256,
                                'messages' => [
                                    StringLength::TOO_SHORT => 'Try Something more longer',
                                    StringLength::TOO_LONG => 'Could you really remember this long identity'
                                ]
                            ],
                        ]
                    ]
                ]);



                $inputFilter->add([
                    'name' => 'address_latitude',
                    'required' => false,
                    "allow_empty" => true,
                    'filters' => [
                        [
                            'name' => 'StripTags'
                        ],
                        [
                            'name' => 'StringTrim'
                        ]
                    ],
                    'validators' => [
                        [
                            'name' => 'NotEmpty',
                            'options' => [
                                'messages' => [
                                    'isEmpty' => 'Address latitude is required'
                                ]
                            ]
                        ],

                        [
                            'name' => StringLength::class,
                            'options' => [
                                // 'messages' => [],
                                'min' => 6,
                                'max' => 256,
                                'messages' => [
                                    StringLength::TOO_SHORT => 'Try Something more longer',
                                    StringLength::TOO_LONG => 'Could you really remember this long identity'
                                ]
                            ],
                        ]
                    ]
                ]);

                $inputFilter->add([
                    'name' => 'account_number',
                    'required' => false,
                    "allow_empty" => true,
                    'filters' => [
                        [
                            'name' => 'StripTags'
                        ],
                        [
                            'name' => 'StringTrim'
                        ]
                    ],

                ]);

                $inputFilter->add([
                    'name' => 'bank',
                    'required' => false,
                    "allow_empty" => true,
                    'filters' => [
                        [
                            'name' => 'StripTags'
                        ],
                        [
                            'name' => 'StringTrim'
                        ]
                    ],

                ]);

                $inputFilter->setData($data);
                if ($inputFilter->isValid()) {
                    $em = $this->entityManager;
                    $values = $inputFilter->getValues();

                    $emailValue = ($values["email"] == NULL ? $values["phonenumber"] . "@recyclepoints.com" : $values["email"]);
                    // $activeUser = $this->apiAuth->getContainerIdentity();

                    $userEntity = new User();
                    $userEntity->setCreatedOn(new \Datetime())
                        ->setUsername($values["phonenumber"])
                        ->setEmail($emailValue)
                        ->setPassword(AuthenticationService::encryptPassword("Simple123"))
                        ->setRole($em->find(Roles::class, AuthenticationService::USER_ROLE_CUSTOMER))
                        ->setFullname($values["fullname"])->setEmailConfirmed(true)
                        ->setIsProfiled(true)->setRegistrationDate(new \Datetime())
                        ->setState($em->find(UserState::class, AuthenticationService::USER_STATE_ENABLED))
                        ->setUid(RegisterService::createUid())->setUuid(RegisterService::createUUid())
                        ->setRegistrationToken(uniqid(mt_rand(), true));
                    $customerEntity = new Customer();
                    $customerEntity->setUser($userEntity)->setCreatedOn(new \Datetime())
                        ->setAddress($values["address"])
                        ->setAddressPlaceId($values["address_google_place_id"])
                        ->setAddressLatitude($data["address_latitude"])
                        ->setCustomerUid(CustomerService::generateCustomerId())
                        ->setCustomerUuid(CustomerService::generareCustomerUuid())
                        ->setIsActive(true)
                        ->setAddressLongitude($data["address_longitude"]);
                    // $busterScavenger = new TrashScavengers();
                    // $busterUser = $em->getRepository(User::class)->findOneBy([
                    //     "uuid" => $activeUser["uuid"]
                    // ]);
                    // $busterScavenger->setCustomerUser($userEntity)
                    //     ->setTrashbusterUser($busterUser)
                    //     ->setCreatedOn(new \Datetime());

                    if (isset($data["account_number"]) && isset($data["bank"])) {
                        $accountName = isset($data["account_name"]) ? $data["account_name"] : "No Name";
                        /**
                         * @var UserBankAccount
                         */
                        $customerBankAccountEntity = new UserBankAccount();
                        $customerBankAccountEntity->setUser($userEntity)->setCreatedOn(new \Datetime())
                            ->setBank($em->find(Banks::class, $data["bank"]))->setAccountName($accountName)->setAccountNumber($data["account_number"]);

                        $em->persist($customerBankAccountEntity);
                    }


                    $em->persist($userEntity);
                    $em->persist($customerEntity);
                    // $em->persist($busterScavenger);

                    $em->flush();

                    $jsonModel->setVariables([
                        "success" => true
                    ]);
                    $response->setStatusCode(201);
                }
            } catch (\Throwable $th) {
                $response->setStatusCode(400);
                $jsonModel->setVariables([
                    "message" => $th->getMessage()
                ]);
            }
        }
        return $jsonModel;
    }





    public static function generatePostId()
    {
        return uniqid("POSTWASTE");
    }

    public static function generatePostUuid()
    {
        $uid = Uuid::uuid4();
        return $uid->toString();
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
     * Get undocumented variable
     *
     * @return  ApiAuthenticateService
     */
    public function getApiAuth()
    {
        return $this->apiAuth;
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
     * Get undocumented variable
     *
     * @return  DoriHostService
     */
    public function getDorihostService()
    {
        return $this->dorihostService;
    }

    /**
     * Set undocumented variable
     *
     * @param  DoriHostService  $dorihostService  Undocumented variable
     *
     * @return  self
     */
    public function setDorihostService(DoriHostService $dorihostService)
    {
        $this->dorihostService = $dorihostService;

        return $this;
    }

    /**
     * Get the value of walletService
     */
    public function getWalletService()
    {
        return $this->walletService;
    }

    /**
     * Set the value of walletService
     *
     * @return  self
     */
    public function setWalletService($walletService)
    {
        $this->walletService = $walletService;

        return $this;
    }
}
