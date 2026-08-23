<?php

namespace Customer\Controller;

use Actors\Entity\AssignedRequest;
use Actors\Entity\AssignedRequestStatus;
use Actors\Service\TrashBustterService;
use Authentication\Service\ApiAuthenticateService;
use Customer\Entity\WasteRequest;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Exception;
use General\Entity\WasteRequestType;
use Laminas\Db\Sql\Ddl\Column\Datetime;
use Laminas\View\Model\ViewModel;
use Customer\InputFilter\RequestWasteInputFilter;
use Customer\Service\CustomerService;
use General\Service\Pusher\PusherService;
use General\Entity\Settings;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Wallet\Service\WalletApiService;
use Customer\InputFilter\DropOffInputFilter;
use Customer\InputFilter\PickUpInputFilter;
use Authentication\Entity\User;
use Authentication\Service\AuthenticationService;
use Customer\Entity\CollectionTypeWasteType;
use Customer\Entity\UpgradeRequest;
use Customer\Entity\UpgradeRequestComment;
use Customer\Entity\WasteRequestActivity;
use General\Entity\WasteCollectionType;
use General\Entity\WasteRequestActivityConst;
use General\Entity\WasteRequestState;
use General\Service\GeneralService;
use Ramsey\Uuid\Uuid;

class CustomerController extends AbstractActionController
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
     * @var RequestWasteInputFilter
     */
    private $requestWasteInputFilter;

    /**
     * Undocumented variable
     *
     * @var Settings
     */
    private $settings;

    /**
     * Undocumented variable
     *
     * @var array
     */
    private $pusherEvents;


    /**
     * Pusher Object
     *
     * @var PusherService
     */
    private $pusherObject;

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
     * @var  DropOffInputFilter
     */
    private $dropoffInputfilter;

    /**
     * Undocumented variable
     *
     * @var PickUpInputFilter
     */
    private $pickupInpufilter;

    /**
     * Undocumented variable
     *
     * @var CustomerService
     */
    private $customerService;

    public function indexAction()
    {
        return new ViewModel();
    }



    public function customerProfileAction()
    {
    }

    public function vieCustomerAction()
    {
        $jsonModel = new JsonModel();
        $em = $this->entityManager;
        return $jsonModel;
    }

    /**
     * Should be used to request a users profile details
     *
     * @OA\GET( path="/customer/api/user-profile", tags={"Customer", "DORIHOST", "TrashBuster"}, description="Should be used to request a users profile details",
     * @OA\Response(response="200", description="Created"),
     * @OA\Response(response="401", description="Not Authorized"),
     * @OA\Response(response="403", description="Error"),
     * security={{"bearerAuth":{}}}
     * )
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function userProfileAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();
        $apiauth = $this->apiAuth;
        $em = $this->entityManager;

        // if ($request->isPost()) {
        // $identity = $this->apiAuth->getContainerIdentity();
        $identity = $apiauth->getContainerIdentity();
        /**
         * @var User
         */
        $userEntity = $em->getRepository(User::class)->findOneBy([
            "uuid" => $identity["uuid"]
        ]);
        $data = [
            "fullname" => $userEntity->getFullname(),
            "email" => $userEntity->getEmail(),
            "phone_number" => $userEntity->getUsername(),
            "role" => $userEntity->getRole()->getId(),
            "wallet" => floatval($userEntity->getWallet()->getBalance()),
            "address" => $userEntity->getCustomer()->getAddress(),
        ];
        $jsonModel->setVariables([
            "data" => $data
        ]);
        return $jsonModel;
    }

    /**
     * Use this endpoint to update the password of a logged in user
     * @OA\POST( path="/customer/api/change-password", tags={"Customer", "DORIHOST", "TrashBuster"}, description="Use this endpoint to update the password of a logged in user",
     * @OA\RequestBody(
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(required={"password", "confirm_password"},
     * @OA\Property(property="password", type="string", example="qwe-wert6-123kklila"),
     * @OA\Property(property="confirm_password", type="string", example="qwe-wert6-123kklila"),
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
     * )
     *
     * @return void
     */
    public function changePasswordAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();
        $apiauth = $this->apiAuth;
        $em = $this->entityManager;

        // if ($request->isPost()) {
        // $identity = $this->apiAuth->getContainerIdentity();
        $identity = $apiauth->getContainerIdentity();
        if ($request->isPost()) {
            $json = $request->getContent();
            $postData = json_decode($json, true);
            try {
                if ($postData["password"] != $postData["confirm_password"]) {
                    throw new \Exception("Passwords do not match");
                } else {
                    /**
                     * @var User
                     */
                    $userEntity = $em->getRepository(User::class)->findOneBy([
                        "uuid" => $identity["uuid"]
                    ]);
                    $encryptedPassword = AuthenticationService::encryptPassword($postData["password"]);
                    $userEntity->setPassword($encryptedPassword)->setUpdatedOn(new \Datetime());

                    $em->persist($userEntity);
                    $em->flush();

                    $response->setStatusCode(201);
                    $jsonModel->setVariables([
                        "data" => [
                            "success" => true,

                        ]
                    ]);
                }
            } catch (\Throwable $th) {
                $jsonModel->setVariables([
                    "data" => [
                        "success" => false,
                        "description" => $th->getMessage()
                    ]
                ]);
                $response->setStatusCode(400);
            }
        }
        return $jsonModel;
    }

    /**
     * used to retrieve pusher events to specific to the customer
     *
     * @OA\GET( path="/customer/api/get-my-pusher-events", tags={"Customer"}, description="Request an upgrade from a customer to a dorihost, should only be accessible to the customer",
     * @OA\Response(response="200", description="Created"),
     * @OA\Response(response="401", description="Not Authorized"),
     * @OA\Response(response="403", description="Error"),
     * security={{"bearerAuth":{}}}
     * )
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function getMyPusherEventsAction()
    {
        $jsonModel = new JsonModel();
        $apiauth = $this->apiAuth;
        $em = $this->entityManager;


        $activeUser = $apiauth->getContainerIdentity();

        $data = $em->getRepository(WasteRequest::class)
            ->createQueryBuilder("p")
            ->select(["p.requestUuid as event"])
            ->leftJoin("p.user", "u")
            ->where("u.uuid = :id")
            ->andWhere("p.isActive = :act")
            ->setParameters([
                "id" => $activeUser["uuid"],
                "act" => TRUE
            ])->getQuery()->getArrayResult();
        $jsonModel->setVariables([
            "data" => $data
        ]);
        return $jsonModel;
    }

    /**
     * Use this endpoint to request the full details about a waste request
     * @OA\POST( path="/customer/api/get-waste-request-details", tags={"Customer"}, description="Use this endpoint to request the full details about a waste request",
     * @OA\RequestBody(
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(required={"wuid"},
     * @OA\Property(property="wuid", type="string", example="qwe-wert6-123kklila", description="This is the uuid of the waste"),
     *
     *
     * )
     * ),
     * ),
     * @OA\Response(response="200", description="Success", content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *
     *
     *
     *                     example={
     * "data": {
     *   "request_type": {
     *     "id": 100,
     *    "type": "Pick Up"
     *  },
     *   "customer_estimated_weight": {
     *    "id": 100,
     *    "weight": "< 50kg"
     *   },
     *  "comfirmed_pick_date": null,
     *   "waste_type": {
     *    "id": 100,
     *    "type": "Plastic",
     *     "kgPrice": "10"
     *   },
     *   "pickup_address": "Fatai Abduwahid street Ijegun, lagos Nigeria",
     *   "address_google_place_id": "dhjijkjsjnlkjdsvnk56",
     *  "address_longitude": "3.4723495",
     *    "address_latitude": "3.4723495",
     *    "datetime_of_request": {
     *      "date": "2009-10-16 21:30:00.000000",
     *    "timezone_type": 3,
     *     "timezone": "UTC"
     *   },
     *  "request_uuid": "37dc2a60-cdae-4f73-8f65-78c65199c85a",
     *  "is_active": true,
     *    "date_created": {
     * "date": "2023-06-20 14:13:45.000000",
     *  "timezone_type": 3,
     *   "timezone": "UTC"
     * },
     *  "note": "I want this package delivered before 10am",
     *   "measured_waste_weight": null
     *  }
     *}
     *                 )
     *             )
     *         }),
     * @OA\Response(response="400", description="Bad Request"),
     * @OA\Response(response="401", description="Not Authorized"),
     * @OA\Response(response="403", description="Not permitted"),
     * security={{"bearerAuth":{}}}
     * )
     *
     * @return void
     */
    public function getWasteRequestDetailsAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();
        $em  = $this->entityManager;
        if ($request->isPost()) {
            $json = $request->getContent();
            $postData = json_decode($json, true);
            try {
                if (!isset($postData["wuid"])) {
                    throw new \Exception("Waste uuid  cannot be empty");
                }
                $wuid = strip_tags($postData["wuid"]);
                $data = $em->getRepository(WasteRequest::class)
                    ->createQueryBuilder("w")
                    ->select([
                        "w", "rs", "rt", "ew", "wt", "dh"
                        // // "w.estimatedWeight",
                        // // "w.wasteType",
                        // "w.confirmedPickupDate",
                        //  "rt.id as request_type",
                        // "w.pickupAddress as pickupp_address",
                        // "w.pickupPlaceId as address_google_place_id",
                        // "w.longitude as address_longitude",
                        // "w.latitude as address_latitude",
                        // "w.requestDatetime as datetime_of_request",
                        // "w.requestUuid as request_uuid",
                        // // "w.wasteRequestState as request_status",
                        // "w.isActive as is_active",
                        // "w.createdOn as date_created",
                        // // "w.dropOffhost as drop_off_host",
                        // // "w.notes as note",
                        // // "w.wasteWeigth as waste_weight",
                        // "rs"
                        // "drop_off_host."
                    ])->leftJoin("w.wasteRequestState", "rs")
                    ->leftJoin("w.requestType", "rt")
                    ->leftjoin("w.estimatedWeight", "ew")
                    ->leftJoin("w.wasteType", "wt")
                    ->leftJoin("w.dropOffhost", "dh")

                    ->where("w.requestUuid = :uuid")
                    ->setParameters([
                        "uuid" => $wuid
                    ])->getQuery()
                    ->getResult(Query::HYDRATE_ARRAY);

                if ($data == null || count($data) == 0) {
                    throw new \Exception("Service does not exist");
                }

                $data = $data[0];
                if ($data["requestType"]["id"] == CustomerService::WASTE_REQUEST_TYPE_PICKUP) {
                    $jsonModel->setVariables([
                        "data" =>
                        [
                            "request_type" => $data["requestType"],
                            "customer_estimated_weight" => $data["estimatedWeight"],
                            "comfirmed_pick_date" => $data["confirmedPickupDate"],
                            "waste_type" => $data["wasteType"],
                            "pickup_address" => $data["pickupAddress"],
                            "address_google_place_id" => $data["pickupPlaceId"],
                            "address_longitude" => $data["longitude"],
                            "address_latitude" => $data["latitude"],
                            "datetime_of_request" => $data["requestDatetime"],
                            "request_uuid" => $data["requestUuid"],
                            "is_active" => $data["isActive"],
                            "date_created" => $data["createdOn"],
                            "note" => $data["note"],
                            "has_confirmed_handshake" => $data["isConfirmedHandshake"],
                            "measured_waste_weight" => $data["wasteWeigth"]
                        ]
                    ]);
                    // $
                } else {
                    $jsonModel->setVariables(
                        [

                            "data" => [
                                "request_type" => $data["requestType"],
                                "customer_estimated_weight" => $data["estimatedWeight"],
                                "waste_type" => $data["wasteType"],
                                "drop_off_host" => $data["dropOffhost"],
                                "datetime_of_request" => $data["requestDatetime"],
                                "request_uuid" => $data["requestUuid"],
                                "is_active" => $data["isActive"],
                                "date_created" => $data["createdOn"],
                                "has_confirmed_handshake" => $data["isConfirmedHandshake"],
                                "measured_waste_weight" => $data["wasteWeigth"]
                            ]
                        ]
                    );
                }

                return $jsonModel;
            } catch (\Throwable $th) {
                $response->setStatusCode(400);
                $jsonModel->setVariables([
                    "success" => false,
                    "description" => $th->getMessage()
                ]);
            }
        }
        return $jsonModel;
    }




    /**
     * This retireves all actvie pickuo request
     *
     *
     * @OA\GET( path="/customer/api/get-all-pick-ups", tags={"Customer"},
     *
     *
     *  @OA\Parameter(
     *
     *   name="page_count",
     *   description="determines how manyitem per page Max = 100 Min is 20",
     *   @OA\Schema(
     *     type="string"
     *   ),
     *   in="query",
     *   required=false
     * ),
     *
     *   @OA\Parameter(
     *
     *   name="order",
     *   description="Order method, Options are either ASC or DESC",
     *   @OA\Schema(
     *     type="string"
     *   ),
     *   in="query",
     *   required=false
     * ),
     *
     * @OA\Parameter(
     *
     *   name="page",
     *   description="Page Number being viewed",
     *   @OA\Schema(
     *     type="string"
     *   ),
     *   in="query",
     *   required=false
     * ),
     *
     *
     *  @OA\Parameter(
     *
     *   name="order_by",
     *   description="Order By ",
     *   @OA\Schema(
     *     type="string"
     *   ),
     *   in="query",
     *   required=false
     * ),
     *
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
     *  "previous_page": 1,
     *  "next_page": 2,
     * "data": {
     *    {
     *      "0": {
     *        "id": 2,
     *        "pickupAddress": "Fatai Abduwahid street Ijegun, lagos Nigeria",
     *        "pickupPlaceId": "dhjijkjsjnlkjdsvnk56",
     *        "longitude": "3.4723495",
     *       "requestDatetime": {
     *         "date": "2009-10-16 21:30:00.000000",
     *         "timezone_type": 3,
     *         "timezone": "UTC"
     *       },
     *       "confirmedPickupDate": null,
     *       "note": "I want this package delivered before 10am",
     *       "latitude": "3.4723495",
     *      "requestId": "req6491b4196f079",
     *       "requestUuid": "37dc2a60-cdae-4f73-8f65-78c65199c85a",
     *       "handshakeCode": "1f576ea3-cac7-44fb-bb09-0420737fac3c",
     *       "isConfirmedHandshake": false,
     *       "isActive": true,
     *       "createdOn": {
     *         "date": "2023-06-20 14:13:45.000000",
     *         "timezone_type": 3,
     *         "timezone": "UTC"
     *       },
     *       "updatedOn": {
     *         "date": "2023-06-20 14:13:45.000000",
     *         "timezone_type": 3,
     *         "timezone": "UTC"
     *       },
     *       "wasteWeigth": null,
     *       "requestType": {
     *          "id": 100,
     *          "type": "Pick Up"
     *        }
     *      },
     *     "user_fullname": "Idowu Yusuf Chukwuma",
     *      "user_email": "ezekiel_a@yahoo.com",
     *      "user_uuid": "0af35857-fa54-4cbf-b59e-e2ab5f537f73"
     *   }
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
     * requires
     *
     *
     */



    public function getAllPickUpsAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();
        $apiauth = $this->apiAuth;
        $em = $this->entityManager;

        // if ($request->isPost()) {
        $activeUser = $apiauth->getContainerIdentity();
        // var_dump("HEREF");
        try {
            $order = ($this->params()->fromQuery("order", null) == null ? "DESC" : "ASC");
            $pageCount = ($this->params()->fromQuery("page_count", 20) > 100 ? 100 : $this->params()->fromQuery("page_count", 20));
            // $pageCount = ($this->params()->fromQuery("page_count", NULL) == null ? "DESC" : "ASC");
            $orderBy = $this->params()->fromQuery("order_by", "id");
            $query = $em->createQueryBuilder()->select(["a", "rt", "u.fullname as user_fullname", "u.email as user_email", "u.uuid as user_uuid"])
                ->from(WasteRequest::class, "a")
                ->leftJoin("a.requestType", "rt")
                ->leftJoin("a.user", "u")
                ->where("u.uuid = :user")
                ->andWhere("a.isActive = :active")
                ->andWhere("rt.id = :rt")
                ->setParameters([
                    "user" => $activeUser["uuid"],
                    "active" => true,
                    "rt" => CustomerService::WASTE_REQUEST_TYPE_PICKUP
                ])
                ->orderBy("a.{$orderBy}", $order)
                ->getQuery()
                ->setHydrationMode(Query::HYDRATE_ARRAY);



            $paginator = new Paginator($query);
            $totalItems = count($paginator);

            $currentPage = ($this->params()->fromQuery("page")) ?: 1;
            $totalPageCount = ceil($totalItems / $pageCount);
            $nextPage = (($currentPage < $totalPageCount) ? $currentPage + 1 : $totalPageCount);
            $previousPage = (($currentPage > 1) ? $currentPage - 1 : 1);

            $records = $paginator->getQuery()->setFirstResult($pageCount * ($currentPage - 1))
                ->setMaxResults($pageCount)
                ->getResult(Query::HYDRATE_ARRAY);

            $jsonModel->setVariables([
                "previous_page" => $previousPage,
                "next_page" => $nextPage,
                "data" => $records
            ]);
        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "description" => $th->getMessage(),
                "trace" => $th->getTrace()
            ]);
        }
        // }
        return $jsonModel;
    }




    /**
     * This retrieves all active drop off request
     *
     *
     * @OA\GET( path="/customer/api/get-all-dropp-off", tags={"Customer"},
     *
     *   @OA\Parameter(
     *
     *   name="order",
     *   description="Order method, Options are either ASC or DESC",
     *   @OA\Schema(
     *     type="string"
     *   ),
     *   in="query",
     *   required=false
     * ),
     *
     * @OA\Parameter(
     *
     *   name="page",
     *   description="Page Number being viewed",
     *   @OA\Schema(
     *     type="string"
     *   ),
     *   in="query",
     *   required=false
     * ),
     *
     *
     *  @OA\Parameter(
     *
     *   name="order_by",
     *   description="Order By ",
     *   @OA\Schema(
     *     type="string"
     *   ),
     *   in="query",
     *   required=false
     * ),
     *
     *@OA\Response(response="201", description="Created",
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
    public function getAllDroppOffAction()
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
                ->where("u.id = :userId")
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
     *
     * If Customer waste request type is pickup, payload is sent to this request
     *
     * @OA\POST( path="/customer/api/pickup", tags={"Customer", "DORIHOST"}, description="Used to send pick payload ",
     *
     * @OA\RequestBody(
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(required={"request_type", "estimated_weight", "waste_type", "pickup_address", "address_google_place_id", "address_longitude", "address_latitude", "datetime_of_pickup"},
     * @OA\Property(property="request_type", type="integer", example="100", description="This is ID of the type of request type , basicly pickup or drop off, reference the endpoint /general/api/get-waste-request-type for "),
     * @OA\Property(property="estimated_weight", type="integer", example="100", description="This is the ID reference of the estimated weight entity defined by /general/api/get-estimated-waste"),
     * @OA\Property(property="waste_type", type="integer", example="100", description="This is the ID of the type of waste, please reference the enpoint /general/api/get-waste-type"),
     * @OA\Property(property="pickup_address", type="string", example="Fatai Abduwahid street Ijegun, lagos Nigeria", description="Address for pickup"),
     * @OA\Property(property="address_google_place_id", type="string", example="dhjijkjsjnlkjdsvnk56", description="Google place ID for the pick up address, exctraxted from the gogle map autocomplete response data"),
     * @OA\Property(property="address_longitude", type="string", example="3.4723495", description="The longitude of the pickup address"),
     * @OA\Property(property="address_latitude", type="string", example="3.4723495", description="The latitude of of pickup address"),
     * @OA\Property(property="datetime_of_pickup", type="string", example="2009-10-16 21:30", description="Prefered data and time for pisck, usually when customer would be home, must be in format 2009-10-16 21:30 :: Y-m-d H:i "),
     *
     * @OA\Property(property="note", type="string", example="I want this package delivered before 10am ", description="Additional information for the package"),
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
    public function pickupAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();
        if ($request->isPost()) {
            $json = $request->getContent();
            $postData = json_decode($json, true);

            $inputFilter = $this->pickupInpufilter;
            $inputFilter->setData($postData);
            if ($inputFilter->isValid()) {
                $data = $inputFilter->getValues();

                try {
                    $res = $this->customerService->pickupWasteService($data);

                    $jsonModel->setVariables([
                        "success" => true,
                        "data" => $res,
                    ]);
                    $response->setStatusCode(201);
                } catch (\Throwable $th) {
                    $jsonModel->setVariables([
                        "success" => false,
                        "description" => $th->getMessage(),
                        "trace" => $th->getTrace()
                    ]);
                    $response->setStatusCode(400);
                }
            } else {
                $jsonModel->setVariables([
                    "success" => false,
                    "description" => $inputFilter->getMessages()
                ]);
                $response->setStatusCode(400);
            }
        }

        return $jsonModel;
    }




    /**
     *
     * This endpoint is used wherever the waste request type is drop off
     *
     * @OA\POST( path="/customer/api/dropoff", tags={"Customer"}, description="Used to post a drop off request",
     *
     * @OA\RequestBody(
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(required={"waste_type", "request_type", "drop_off_host", "estimated_weight", "datetime_of_drop_off"},
     * @OA\Property(property="waste_type", type="integer", example="100", description="The type of waste for , be it plastic, metal or composte, refer to the get wastetype endpoint"),
     * @OA\Property(property="request_type", type="integer", example="200", description="A reference to waste request type endpoint "),
     * @OA\Property(property="note", type="string", example="i would have 10kg waste", description="A quick note for the Dori host"),
     * @OA\Property(property="drop_off_host", type="integer", example="15", description="User ID of user/ Dori the drop off will happen refer to the suggested drop off host endpoint reference the UserId Table "),
     * @OA\Property(property="estimated_weight", type="integer", example="100", description="This is the ID reference of the estimated weight entity defined by /general/api/get-estimated-waste"),
     * @OA\Property(property="datetime_of_drop_off", type="string(datetime)",example="2018-12-20 6:06",  description="Date  time customer selected for the drop off"),
     * )
     * ),
     * ),
     *   @OA\Response(response="201", description="Created",
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
     *       "success": true,
     * "data": {
     *   "waste_type": "Plastic",
     * "drop_off_host_name": "Latest Dori",
     *  "drop_off_host_address": "Adeniran Ogunsanya St, Surulere 101241, Lagos, Nigeria"
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
    public function dropoffAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();
        if ($request->isPost()) {
            $json = $request->getContent();
            $postData = json_decode($json, true);
            // var_dump($postData);
            $inputFilter = $this->dropoffInputfilter;
            $inputFilter->setData($postData);
            if ($inputFilter->isValid()) {
                $data = $inputFilter->getValues();
                try {
                    $droppoff = $this->customerService->dropoffWasteService($data);
                    $jsonModel->setVariables([
                        "success" => true,
                        "data" => $droppoff
                    ]);
                    $response->setStatusCode(201);
                } catch (\Throwable $th) {
                    $jsonModel->setVariables([
                        "success" => false,
                        "description" => $th->getMessage()
                    ]);
                    $response->setStatusCode(400);
                }
            } else {
                $jsonModel->setVariables([
                    "success" => false,
                    "description" => $inputFilter->getMessages()
                ]);
                $response->setStatusCode(400);
            }
        }

        return $jsonModel;
    }



    // /**
    //  *
    //  * This function post a series of data for the customer to make a request for pick up or drop of waste
    //  *
    //  * @OA\POST( path="/customer/api/request-waste-collection", tags={"Customer"}, description="Used to get Statistics inforamtion about the package about to be delivered this information is only required to be displayed only, once this is successfully acquired, a call to flutterwave payment gateway should be made, the",
    //  *
    //  * @OA\RequestBody(
    //  * @OA\MediaType(
    //  * mediaType="application/json",
    //  * @OA\Schema(required={"destinationPlaceId", "pickUpPlaceId", "destinationAddress", "pickAddress", "pickupLong", "pickupLat", "destinationLat", "destinationLong", "quantity", "iten_name"},
    //  * @OA\Property(property="destinationPlaceId", type="string", example="EjFJbnQnbCBBaXJwb3J0IFJkLCBNYWZvbHVrdSBPc2hvZGksIExhZ29zLCBOaWdlcmlhIi4qLAoUChIJjagx-h6OOxARwyZNkX_GTysSFAoSCZk5KvookjsQEfChu91LMqjX", description="Google Place id (Unique) of the Destination"),
    //  * @OA\Property(property="pickUpPlaceId", type="string", example="ChIJS9Q72lL0OxARKJ3cGrQfM0c", description="Google Place id (Unique) of the pickup"),
    //  * @OA\Property(property="pickAddress", type="string", example="Kola Oyewo street surulere, lagos Nigeria", description="Pick up Address"),
    //  * @OA\Property(property="destinationAddress", type="string", example="Fatai Abduwahid street Ijegun, lagos Nigeria", description="Destination Address"),
    //  * @OA\Property(property="pickupLat", type="string", example="3.4723495", description="The latitude of the pickup address"),
    //  * @OA\Property(property="pickupLong", type="string", example="3.4723495", description="The longitude of the pickup address"),
    //  * @OA\Property(property="destinationLat", type="string", example="3.4723495", description="The latitude of the destination address "),
    //  * @OA\Property(property="destinationLong", type="string", example="3.4723495", description="The longitude of the destination address "),
    //  * @OA\Property(property="quantity", type="integer", example=2, description="The qauntity of the item"),
    //  * @OA\Property(property="item_name", type="string", example="Bag of oranges", description="Identifier description of tha package"),
    //  * @OA\Property(property="service_type", type="integer", example=10, description="This is an id referenced from the logistics/logistics/service-type url"),
    //  * @OA\Property(property="delivery_type", type="integer", example=10, description="This is an id referenced from the logistics/logistics/delivery-type url"),
    //  * @OA\Property(property="note", type="string", example="I want this package delivered before 10am ", description="Additional information for the package"),
    //  *
    //  * )
    //  * ),
    //  * ),
    //  * @OA\Response(response="201", description="Success"),
    //  * @OA\Response(response="400", description="Bad Request"),
    //  * @OA\Response(response="401", description="Not Authorized"),
    //  * @OA\Response(response="403", description="Not permitted"),
    //  *
    //  * security={{"bearerAuth":{}}}
    //  *
    //  * )
    //  *
    //  * requires
    //  *
    //  * @return \Laminas\View\Model\JsonModel
    //  */
    // public function requestWasteCollectionAction()
    // {
    //     $jsonModel = new JsonModel();
    //     $request = $this->getRequest();
    //     $response = $this->getResponse();
    //     if ($request->isPost()) {
    //         $json = $request->getContent();
    //         $postData = json_decode($json, true);

    //         $inputFilter = $this->requestWasteInputFilter;
    //         $inputFilter->setData($postData);
    //         if ($inputFilter->isValid()) {
    //             $data = $inputFilter->getValues();

    //             try {
    //                 //code...
    //             } catch (\Throwable $th) {
    //                 //throw $th;
    //             }
    //         } else {
    //         }
    //     }

    //     return $jsonModel;
    // }

    /**
     * Use this to cancel any waste request generated
     * @OA\POST( path="/customer/api/cancel-waste-request", tags={"Customer"}, description="Use this endpoint to request another account confimation code",
     * @OA\RequestBody(
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(required={"wuid"},
     * @OA\Property(property="wuid", type="string", example="qwe-wert6-123kklila"),
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
     * )
     *
     * @return void
     */
    public function cancelWasteRequestAction()
    {
        $jsonModel = new JsonModel();
        try {
            $request = $this->getRequest();
            $response = $this->getResponse();
            if ($request->isPost()) {
                $json = $request->getContent();
                $postData = json_decode($json, true);
                $id = $postData["wuid"];
                if ($id == null) {
                    throw new \Exception("Please supple with a waste request identity");
                }
                // $post = $request->getPost();
                /**
                 * @var WasteRequest
                 */
                $wasteRequestEntity = $this->entityManager->getRepository(WasteRequest::class)->findOneBy([
                    "requestUuid" => strip_tags($id),
                    // "user"=>
                ]);
                if ($wasteRequestEntity == null) {
                    throw new \Exception("Please create a waste request");
                }
                $wasteRequestEntity->setIsActive(false)->setUpdatedOn(new \Datetime());

                $this->entityManager->persist($wasteRequestEntity);
                $this->entityManager->flush();

                $response->setStatusCode(204);
            }
        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "description" => $th->getMessage(),
            ]);
        }

        return $jsonModel;
    }


    /**
     * Used to trigger a handshake with a customer by a dori host, or trashbuster
     * The Dori host or buster must have entered the wieght in kg of product
     * this value is trigered by the Dori hhost and the value is confirmed by the customer
     * @OA\POST( path="/customer/api/trigger-handshake", tags={"DORIHOST", "TrashBuster"}, description="Used to trigger a handshake with a customer by a dori host, or trashbuster, which futher trigger the event trigger_dori_host_handshake on pusher, so developer needs to listen for this event",
     *
     * @OA\RequestBody(
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(required={"wuid", "w_weight", "customer_type"},
     * @OA\Property(property="wuid", type="string", example="wer67-hjurkiu7jj-hyyyt", description="This is the waste request uuid"),
     * @OA\Property(property="w_weight", type="integer", example="23", description="This is the weigth in kilograms of the waste the customer has brough to the DORI HOST"),
     * @OA\Property(property="customer_type", type="integer", example="200", description="This is the waste collection type, please regerefence the waste collection type table"),
     * )
     * ),
     * ),
     * @OA\Response(response="200", description="Success"),
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
    public function triggerHandshakeAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getREsponse();
        $em = $this->entityManager;
        try {
            if ($request->isPost()) {
                $json = $request->getContent();
                $postData = json_decode($json, true);
                if (!isset($postData["wuid"]) || !isset($postData["w_weight"]) || !isset($postData["customer_type"])) {
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
                    if ($wasteRequestEntity == null) {
                        throw new \Exception("This request does not exist");
                    }
                    $wasteRequestEntity->setUpdatedOn(new \Datetime())
                        ->setWasteWeigth($w_weight)
                        ->setWasteCollectionType($em->find(WasteCollectionType::class, $postData["customer_type"]))
                        ->setHandshakeCode(CustomerService::generateHandshakeCode());

                    $em->persist($wasteRequestEntity);
                    $em->flush();

                    $pusherData["handshake_code"] = $wasteRequestEntity->getHandshakeCode();
                    $pusherData["waste_weight"] = $w_weight;
                    $pusherData["request_uuid"] = $wasteRequestEntity->getRequestUuid();
                    $pusherData["request_intiator"] = $identity->getId();
                    $pusherData["event_name"] = "trigger_handshake";

                    $this->pusherObject->getPusherObject()->trigger($this->settings->getPusherChannel(), $wasteRequestEntity->getRequestUuid(), $pusherData);
                    $response->setStatusCode(200);
                    $jsonModel->setVariables([
                        "success" => true,
                        "description" => "Retrieved data"
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
     * Used to accept a handshake with a customer by a dori host or buster
     * The Dori host must have enter the wirght in kg of product
     * this value is trigered by the ori hhost and the value is confirmed by the customer
     * @OA\POST( path="/customer/api/accept-handshake", tags={"DORIHOST", "Customer"}, description="Used to trigger a handshake with a customer by a dori host",
     *
     * @OA\RequestBody(
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(required={"wuid",  "handshake_code"},
     * @OA\Property(property="wuid", type="string", example="wer67-hjurkiu7jj-hyyyt", description="This is the waste request uuid"),
     * @OA\Property(property="handshake_code", type="string", example="45758889", description="This is retrieved from the pusher payload triggered by the trashbuster or DOrihost"),
     * @OA\Property(property="request_intiator", type="integer", example="100", description="This is retrieved from the pusher payload triggered by the trashbuster or DOrihost and only applicatble if the requestType is pickup"),
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
    public function acceptHandshakeAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $em = $this->entityManager;
        $response = $this->getResponse();
        $identity = $this->apiAuth->getContainerIdentity();
        if ($request->isPost()) {
            $json = $request->getContent();
            $postData = json_decode($json, true);
            try {
                if ($postData["wuid"] == null || $postData["handshake_code"] == null) {
                    throw new \Exception("A required field is empty");
                } else {
                    $wuid = $postData["wuid"];

                    /**
                     * @var WasteRequest
                     *
                     */
                    $wasteRequestEntity = $em->getRepository(WasteRequest::class)->findOneBy([
                        "requestUuid" => $wuid
                    ]);
                    $userEntity = $em->getRepository(User::class)->findOneBy([
                        "uuid" => $identity["uuid"]
                    ]);
                    if ($wasteRequestEntity == null) {
                        throw new \Exception("This request does not exist");
                    }
                    if ($postData["handshake_code"] != $wasteRequestEntity->getHandshakeCode()) {
                        throw new \Exception("hanshake verification eroor");
                    } else {
                        // call wallet api service

                        $wasteRequestEntity->setIsConfirmedHandshake(true)
                            ->setUpdatedOn(new \Datetime())
                            ->setWasteRequestState($em->find(WasteRequestState::class, GeneralService::WASTE_REQUEST_STATE_COMPLETED));

                        $wasteRequestActivityEntity = new WasteRequestActivity();
                        $wasteRequestActivityEntity->setCreatedOn(new \DateTime())
                            ->setInititor($userEntity)
                            ->setActivity($em->find(WasteRequestActivityConst::class, GeneralService::REQUEST_ACTIVITY_HANDSHAKE_CONFIMRED))
                            ->setDescription("{$userEntity->getFullname()} has accepted handshake")
                            ->setWasteRequest($wasteRequestEntity);


                        /**
                         * @var WalletApiService
                         */
                        $walletApiService = $this->walletApiService;
                        $shakeData["weight"] = $wasteRequestEntity->getWasteWeigth();
                        // wasteRequestEntity->getWasteType()->getKgPrice();
                        /**
                         * @var CollectionTypeWasteType
                         */
                        $wasteCollectionTypeWasteTypeEntity = $em->getRepository(CollectionTypeWasteType::class)->findOneBy([
                            "collectionType" => $wasteRequestEntity->getWasteCollectionType()->getId(),
                            "wasteType" => $wasteRequestEntity->getWasteType()->getId()
                        ]);
                        $shakeData["kg_price"] = $wasteCollectionTypeWasteTypeEntity->getPricePerUnit();




                        $cata["credit"] = CustomerService::calculateCustomerCredit($shakeData);
                        // $data[""]
                        $walletApiService->creditWallet($cata);
                        // calculate Credit unit for customer
                        // Send Emails of Credit to customer

                        if ($wasteRequestEntity->getRequestType()->getId() == CustomerService::WASTE_REQUEST_TYPE_PICKUP) {
                            /**
                             * @var  AssignedRequest
                             */
                            $assignedRequestEntity = $em->getRepository(AssignedRequest::class)->findOneBy([
                                "wasteRequest" => $wasteRequestEntity->getId(),
                                "assignedTo" => $postData["request_intiator"]
                            ]);
                            if ($assignedRequestEntity != NULL) {
                                $assignedRequestEntity->setAsignedRequestStatus($em->find(AssignedRequestStatus::class, TrashBustterService::ASSIGNED_REQUEST_STATUS_COMPLETED))->setCompletedOn(new \DateTime())
                                    ->setUpdatedOn(new \Datetime());

                                $em->persist($assignedRequestEntity);
                            }
                        }

                        $em->persist($wasteRequestActivityEntity);
                        $em->persist($wasteRequestEntity);
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
                        $response->setStatusCode(201);
                    }
                }
            } catch (\Throwable $th) {
                $jsonModel->setVariables([
                    "data" => [
                        "success" => false,
                        "description" => $th->getMessage()
                    ]
                ]);
                $response->setStatusCode(400);
            }
        }
        return $jsonModel;
    }


    /**
     *
     * Receives the waste uuid and Returns the code for handshake between customer and dori host
     *
     * @OA\POST( path="/customer/api/get-handshake", tags={"DORIHOST","Customer"}, description="Generate the waste handshake code",
     *
     * @OA\RequestBody(
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(required={"wuid"},
     * @OA\Property(property="wuid", type="string", example="wer67-hjurkiu7jj-hyyyt", description="This is the waste request uuid"),
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
    public function getHandshakeAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $em = $this->entityManager;
        $response = $this->getResponse();
        if ($request->isPost()) {
            $json = $request->getContent();
            $postData  = json_decode($json, true);
            if (!isset($postData["wuid"])) {
                $response->setStatusCode(400);
                $jsonModel->setVariables([
                    "success" => false,
                    "decription" => "Waste uuid is required and cannot be empty"
                ]);
            } else {
                try {
                    $wuid = $postData["wuid"];
                    /**
                     * @var WasteRequest
                     */
                    $wasteRequestEntity = $em->getRepository(WasteRequest::class)->findOneBy([
                        "requestUuid" => $wuid
                    ]);
                    if ($wasteRequestEntity == null) {
                        throw new \Exception("This waste request really does not exist");
                    }
                    $jsonModel->setVariables([
                        "code" => $wasteRequestEntity->getHandshakeCode()
                    ]);
                    $response->setStatusCode(200);
                } catch (\Throwable $th) {
                    $response->setStatusCode(400);
                    $jsonModel->setVariables([
                        "success" => false,
                        "decription" => $th->getMessage()
                    ]);
                }
            }
        }
        return $jsonModel;
    }




    /**
     * This gets available and associated nearby host
     *
     * @OA\GET( path="/customer/api/get-nearby-host", tags={"Customer"}, description="This gets available and associated nearby host",
     *  @OA\Response(response="200", description="Success",
     *  content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         description="Defines the state of the request"
     *                     ),
     *
     *
     *                     example={
     *           "data": {
     *{
     * "id": 51,
     *"distanceAway": "0",
     *"customer": {
     *  "id": 13,
     *  "fullname": "Idowu Yusuf Chukwuma",
     *  "email": "ezekiel_a@yahoo.com"
     *},
     *"dorihost": {
     *  "id": 15,
     *  "fullname": "Idowu Yusuf Chukwuma",
     *  "username": "07034896793",
     *  "email": "swoopfx@gmail.com",
     *  "role": {
     *    "id": 150,
     *    "name": "DORI HOST"
     *  },
     *  "customer": {
     *    "id": 12,
     *    "address": "15 Jacob adeleye street",
     *    "addressLongitude": "3.3497862",
     *    "addressLatitude": "6.506842699999999"
     *  }
     *}
     *},
     *{
     *"id": 52,
     *"distanceAway": "0",
     *"customer": {
     *  "id": 13,
     *  "fullname": "Idowu Yusuf Chukwuma",
     *  "email": "ezekiel_a@yahoo.com"
     *},
     *"dorihost": {
     *  "id": 17,
     *  "fullname": "Kola Ajayi",
     *  "username": "08038558468",
     *  "email": "otabayomi@gmail.com",
     *  "role": {
     *    "id": 150,
     *    "name": "DORI HOST"
     *  },
     *  "customer": {
     *    "id": 13,
     *    "address": "15 Jacob adeleye street",
     *    "addressLongitude": "3.3497862",
     *    "addressLatitude": "6.506842699999999"
     *  }
     *}
     *},
     *{
     *"id": 53,
     *"distanceAway": "1.9461198982141",
     *"customer": {
     *  "id": 13,
     *  "fullname": "Idowu Yusuf Chukwuma",
     *  "email": "ezekiel_a@yahoo.com"
     *},
     *"dorihost": {
     *  "id": 23,
     *  "fullname": "Usain Afolabi",
     *   "username": "1100000001",
     *  "email": "adcd@gmail.com",
     *  "role": {
     *    "id": 150,
     *    "name": "DORI HOST"
     *  },
     *  "customer": {
     *    "id": 14,
     *    "address": "Igun St, Itire 102215, Lagos, Nigeria",
     *     "addressLongitude": "3.3322308",
     *    "addressLatitude": "6.5082857"
     *  }
     *}
     *},
     *{
     *"id": 54,
     *"distanceAway": "1.6231594342122",
     *"customer": {
     *  "id": 13,
     *  "fullname": "Idowu Yusuf Chukwuma",
     *  "email": "ezekiel_a@yahoo.com"
     *},
     *"dorihost": {
     *  "id": 26,
     *  "fullname": "Latest Dori",
     *  "username": "89923456",
     *  "email": "19@gmail.com",
     *  "role": {
     *    "id": 150,
     *    "name": "DORI HOST"
     *  },
     *  "customer": {
     *    "id": 17,
     *    "address": "Adeniran Ogunsanya St, Surulere 101241, Lagos, Nigeria",
     *    "addressLongitude": "3.3573732",
     *    "addressLatitude": "6.4943423"
     *  }
     *}
     *}
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
     * @OA\Response(response="401", description="Not Authorized"),
     * @OA\Response(response="403", description="Error"),
     * security={{"bearerAuth":{}}}
     * )
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function getNearbyHostAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();
        try {
            $nearByHost = $this->customerService->getNearestHost();
            $jsonModel->setVariables([
                "data" => $nearByHost
            ]);
        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "description" => $th->getMessage()
            ]);
        }
        return $jsonModel;
    }



    /**
     * This seraches for nearby DORI HOST, should only be called if host /get-nearby-host come empty
     *
     * @OA\GET( path="/customer/api/search-nearby-host", tags={"Customer"}, description="This seraches for nearby DORI HOST, should only be called if host /get-nearby-host come empty",
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
     *           "data": {
     *{
     * "id": 51,
     *"distanceAway": "0",
     *"customer": {
     *  "id": 13,
     *  "fullname": "Idowu Yusuf Chukwuma",
     *  "email": "ezekiel_a@yahoo.com"
     *},
     *"dorihost": {
     *  "id": 15,
     *  "fullname": "Idowu Yusuf Chukwuma",
     *  "username": "07034896793",
     *  "email": "swoopfx@gmail.com",
     *  "role": {
     *    "id": 150,
     *    "name": "DORI HOST"
     *  },
     *  "customer": {
     *    "id": 12,
     *    "address": "15 Jacob adeleye street",
     *    "addressLongitude": "3.3497862",
     *    "addressLatitude": "6.506842699999999"
     *  }
     *}
     *},
     *{
     *"id": 52,
     *"distanceAway": "0",
     *"customer": {
     *  "id": 13,
     *  "fullname": "Idowu Yusuf Chukwuma",
     *  "email": "ezekiel_a@yahoo.com"
     *},
     *"dorihost": {
     *  "id": 17,
     *  "fullname": "Kola Ajayi",
     *  "username": "08038558468",
     *  "email": "otabayomi@gmail.com",
     *  "role": {
     *    "id": 150,
     *    "name": "DORI HOST"
     *  },
     *  "customer": {
     *    "id": 13,
     *    "address": "15 Jacob adeleye street",
     *    "addressLongitude": "3.3497862",
     *    "addressLatitude": "6.506842699999999"
     *  }
     *}
     *},
     *{
     *"id": 53,
     *"distanceAway": "1.9461198982141",
     *"customer": {
     *  "id": 13,
     *  "fullname": "Idowu Yusuf Chukwuma",
     *  "email": "ezekiel_a@yahoo.com"
     *},
     *"dorihost": {
     *  "id": 23,
     *  "fullname": "Usain Afolabi",
     *   "username": "1100000001",
     *  "email": "adcd@gmail.com",
     *  "role": {
     *    "id": 150,
     *    "name": "DORI HOST"
     *  },
     *  "customer": {
     *    "id": 14,
     *    "address": "Igun St, Itire 102215, Lagos, Nigeria",
     *     "addressLongitude": "3.3322308",
     *    "addressLatitude": "6.5082857"
     *  }
     *}
     *},
     *{
     *"id": 54,
     *"distanceAway": "1.6231594342122",
     *"customer": {
     *  "id": 13,
     *  "fullname": "Idowu Yusuf Chukwuma",
     *  "email": "ezekiel_a@yahoo.com"
     *},
     *"dorihost": {
     *  "id": 26,
     *  "fullname": "Latest Dori",
     *  "username": "89923456",
     *  "email": "19@gmail.com",
     *  "role": {
     *    "id": 150,
     *    "name": "DORI HOST"
     *  },
     *  "customer": {
     *    "id": 17,
     *    "address": "Adeniran Ogunsanya St, Surulere 101241, Lagos, Nigeria",
     *    "addressLongitude": "3.3573732",
     *    "addressLatitude": "6.4943423"
     *  }
     *}
     *}
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
     * @OA\Response(response="401", description="Not Authorized"),
     * @OA\Response(response="403", description="Error"),
     * security={{"bearerAuth":{}}}
     * )
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function searchNearbyHostAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();
        try {

            $this->customerService->searchNearestHost();
            $nearbyHost = $this->customerService->getNearestHost();

            $jsonModel->setVariables([
                "data" => $nearbyHost
            ]);
        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "description" => $th->getMessage(),
                // "desc" => $th->getTrace(),
            ]);
        }
        return $jsonModel;
    }


    public function selectNearbyHostAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();
        if ($request->isPost()) {
            $json = $request->getContent();
            $postData = json_decode($json, true);
            try {
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
        return $jsonModel;
    }






    /**
     * Request an upgrade from a customer to a dorihost, should only be accessible to the customer
     *
     * @OA\GET( path="/customer/api/request-upgrade", tags={"Customer"}, description="Request an upgrade from a customer to a dorihost, should only be accessible to the customer",
     * @OA\Response(response="201", description="Created"),
     * @OA\Response(response="401", description="Not Authorized"),
     * @OA\Response(response="403", description="Error"),
     * security={{"bearerAuth":{}}}
     * )
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function requestUpgradeAction()
    {
        $jsonModel = new JsonModel();
        $em = $this->entityManager;
        $response = $this->getResponse();
        $identity = $this->apiAuth->getContainerIdentity();
        // var_dump()
        if ($identity["role"] != 100) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "description" => "You must be a customer to access this function"
            ]);
        } else {
            try {
                $userEntity = $this->entityManager->getRepository(User::class)->findOneBy([
                    "uuid" => $identity["uuid"]
                ]);
                $updateEntity = new UpgradeRequest();
                $updateEntity->setCreatedOn(new \Datetime())->setIsActive(true)->setRequestUser($userEntity)->setUuid(Uuid::uuid4());

                $em->persist($updateEntity);
                $em->flush();

                // send email
                // Notfication (Pusher)
                $jsonModel->setVariables([
                    "success" => true,
                ]);
                $response->setStatusCode(201);
            } catch (\Exception $e) {
                $jsonModel->setVariables([
                    "success" => false,
                ]);
                $response->setStatusCode(400);
            }
        }
        // checks the present role status of the user;
        //hydrate the request

        return $jsonModel;
    }


    /**
     *
     * @OA\Parameter(
     *   parameter="upID_in_query",
     *   name="uuid",
     *   description="Upgrade Request UUID",
     *  example="jurt574-ujtuuyi-578mnhtr-ouutj",
     *   @OA\Schema(
     *     type="string"
     *   ),
     *   in="path",
     *   required=true
     * )
     *
     * @OA\GET( path="/customer/api/request-upgrade-comment/{uuid}", tags={"Customer"}, @OA\Parameter(ref="#/components/parameters/upID_in_query"),
     *
     * @OA\Response(response="200", description="Success"),
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
    public function requestUpgradeCommentAction()
    {
        $jsonModel = new JsonModel();
        $response = $this->getResponse();
        $uuid = $this->params()->fromRoute("id", NULL);
        if ($uuid == NULL) {
            $jsonModel->setVariables([
                "success" => false,
                "message" => "Absent identifier"
            ]);
            $response->setStatusCode(400);
            return $jsonModel;
        }
        $em = $this->entityManager;
        $data = $em->getRepository(UpgradeRequestComment::class)
            ->createQueryBuilder("u")->select(["u", "r"])
            ->leftJoin("u.request", "r")
            ->where("r.uuid = :uuid")->setParameters([
                "uuid" => $uuid
            ])
            ->getQuery()
            ->getArrayResult();

        $jsonModel->setVariables([
            "data" => $data
        ]);
        return $jsonModel;
    }


    public function upgradeRequestListAction()
    {
        $jsonModel = new JsonModel();
        $em = $this->entityManager;
        $activeUser = $this->apiAuth->getContainerIdentity();
        $response = $this->getResponse();
        $data = $em->getRepository(UpgradeRequest::class)
            ->createQueryBuilder("u")->select(["u.uuid", "r"])
            ->leftJoin("u.request", "r")
            ->where("r.uuid = :user")->setParameters([
                "user" => $activeUser["uuid"]
            ])
            ->getQuery()
            ->getArrayResult();

        $jsonModel->setVariables([
            "data" => $data
        ]);
        return $jsonModel;
    }









    // /**
    //  * @OA\GET( path="/customer/api/get-waste-request-type", tags={"Customer"}, description="Retrieve waste request Type",
    //  * @OA\Response(response="200", description="Success"),
    //  * @OA\Response(response="401", description="Not Authorized"),
    //  * @OA\Response(response="403", description="Error"),
    //  * security={{"bearerAuth":{}}}
    //  * )
    //  *
    //  * @return \Laminas\View\Model\JsonModel
    //  */
    // public function getWasteRequestTypeAction()
    // {
    //     $jsonModel = new JsonModel();
    //     $data = $this->entityManager->getRepository(WasteRequestType::class)->createQueryBuilder("a")
    //         ->select(["a"])
    //         ->getQuery()->getArrayResult();
    //     $jsonModel->setVariables([
    //         "data" => $data
    //     ]);
    //     return $jsonModel;
    // }

    // public function

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
     * @return  RequestWasteInputFilter
     */
    public function getRequestWasteInputFilter()
    {
        return $this->requestWasteInputFilter;
    }

    /**
     * Set undocumented variable
     *
     * @param  RequestWasteInputFilter  $requestWasteInputFilter  Undocumented variable
     *
     * @return  self
     */
    public function setRequestWasteInputFilter(RequestWasteInputFilter $requestWasteInputFilter)
    {
        $this->requestWasteInputFilter = $requestWasteInputFilter;

        return $this;
    }

    /**
     * Get pusher Object
     *
     * @return  PusherService
     */
    public function getPusherObject()
    {
        return $this->pusherObject;
    }

    /**
     * Set pusher Object
     *
     * @param  PusherService  $pusherObject  Pusher Object
     *
     * @return  self
     */
    public function setPusherObject(PusherService $pusherObject)
    {
        $this->pusherObject = $pusherObject;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  Settings
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Set undocumented variable
     *
     * @param  Settings  $settings  Undocumented variable
     *
     * @return  self
     */
    public function setSettings(Settings $settings)
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  array
     */
    public function getPusherEvents()
    {
        return $this->pusherEvents;
    }

    /**
     * Set undocumented variable
     *
     * @param  array  $pusherEvents  Undocumented variable
     *
     * @return  self
     */
    public function setPusherEvents(array $pusherEvents)
    {
        $this->pusherEvents = $pusherEvents;

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
     * @param  DropOffInputFilter  $dropoffInputfilter  Undocumented variable
     *
     * @return  self
     */
    public function setDropoffInputfilter(DropOffInputFilter $dropoffInputfilter)
    {
        $this->dropoffInputfilter = $dropoffInputfilter;

        return $this;
    }

    /**
     * Set undocumented variable
     *
     * @param  PickUpInputFilter  $pickupInpufilter  Undocumented variable
     *
     * @return  self
     */
    public function setPickupInpufilter(PickUpInputFilter $pickupInpufilter)
    {
        $this->pickupInpufilter = $pickupInpufilter;

        return $this;
    }

    /**
     * Set undocumented variable
     *
     * @param  CustomerService  $customerService  Undocumented variable
     *
     * @return  self
     */
    public function setCustomerService($customerService)
    {
        $this->customerService = $customerService;

        return $this;
    }
}
