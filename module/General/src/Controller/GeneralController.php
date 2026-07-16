<?php

namespace General\Controller;

use Authentication\Entity\User;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use General\Entity\Gender;
use General\Entity\Settings;
use General\Entity\WasteRequestType;
use General\Entity\WasteType;
use General\Service\GeneralService;
use Authentication\Service\ApiAuthenticateService;
use General\Entity\EstimatedWeight;
use General\Entity\PostWasteStatus;
use General\Entity\WasteCollectionType;
use Shop\Entity\OrderStatus;
use General\Entity\Banks;

class GeneralController extends AbstractActionController
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
     * @var GeneralService
     */
    private $generalService;

    /**
     * Undocumented variable
     *
     * @var ApiAuthenticateService
     */
    private $apiAuth;

    /**
     * Used to retrieve list of gender
     * @OA\GET( path="/general/api/get-gender", tags={"General"},
     * @OA\Response(response="200", description="Success"),
     * @OA\Response(response="403", description="Error"),
     * security={{"bearerAuth":{}}}
     * )
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function getGenderAction()
    {
        $jsonModel = new JsonModel();
        $response = $this->getResponse();

        try {
            $data = $this->entityManager
                ->getRepository(Gender::class)
                ->createQueryBuilder("g")
                ->select("g")
                ->getQuery()
                ->getResult(Query::HYDRATE_ARRAY);
            $jsonModel->setVariables([
                "data" => $data
            ]);
        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "message" => "Something went wrong , please try agai later"
            ]);
        }

        // var_dump($this->apiAuth->getContainerIdentity());
        return $jsonModel;
    }


    /**
     * Used to retrieve The Users Profile
     * @OA\GET( path="/general/api/get-user-profile", tags={"General"},
     * @OA\Response(response="200", description="Success"),
     * @OA\Response(response="403", description="Error"),
     * security={{"bearerAuth":{}}}
     * )
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function getUserProfileAction()
    {
        $jsonModel = new JsonModel();
        $response = $this->getResponse();
        // $id = $this->params()->fromRoute("id", NULL);

        try {
            // if ($id == NULL) {
            //     throw new \Exception("absent Identity");
            // }
            $identity = $this->apiAuth->getContainerIdentity();
            // var_dump($identity["uuid"]);
            // $userEntity = $this->entityManager->getRepository(User::class)->findOneBy([
            //     "uuid" => $identity["uuid"]
            // ]);
            // var_dump($userEntity->getId());
            $data = $this->entityManager
                ->getRepository(User::class)
                ->createQueryBuilder("u")
                ->select([
                    "partial u.{id, email, fullname, username, registrationDate, uid, uuid}",
                    "partial w.{id, walletUid, walletUuid, balance}",
                    "partial r.{id, name}"
                ])->leftJoin("u.role", "r")
                ->leftJoin("u.wallet", "w")
                ->where("u.uuid = :uuid")
                ->setParameters([
                    "uuid" => $identity["uuid"]
                ])
                ->getQuery()
                ->getResult(Query::HYDRATE_ARRAY);
            $jsonModel->setVariables([
                "data" => $data
            ]);
        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "message" => "Something went wrong , please try agai later",
                "dec" => $th->getMessage()
            ]);
        }

        // var_dump($this->apiAuth->getContainerIdentity());
        return $jsonModel;
    }

    /**
     * Used to retirve a list of waste Type
     * @OA\GET( path="/general/api/get-waste-type", tags={"General"},
     * @OA\Response(response="200", description="Success"),
     * @OA\Response(response="403", description="Error"),
     * security={{"bearerAuth":{}}}
     * )
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function getWasteTypeAction()
    {
        $jsonModel = new JsonModel();
        $response = $this->getResponse();

        try {
            $data = $this->entityManager
                ->getRepository(WasteType::class)
                ->createQueryBuilder("g")
                ->select("g")
                ->getQuery()
                ->getResult(Query::HYDRATE_ARRAY);
            $jsonModel->setVariables([
                "data" => $data
            ]);
        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "message" => "Something went wrong , please try agai later"
            ]);
        }

        return $jsonModel;
    }


    /**
     * Used to retirve a list of waste Type
     * @OA\GET( path="/general/api/get-waste-collection-type", tags={"General"},
     * @OA\Response(response="200", description="Success"),
     * @OA\Response(response="403", description="Error"),
     * security={{"bearerAuth":{}}}
     * )
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function getWasteCollectionTypeAction()
    {
        $jsonModel = new JsonModel();
        $response = $this->getResponse();

        try {
            $data = $this->entityManager
                ->getRepository(WasteCollectionType::class)
                ->createQueryBuilder("g")
                ->select("g")
                ->getQuery()
                ->getResult(Query::HYDRATE_ARRAY);
            $jsonModel->setVariables([
                "data" => $data
            ]);
        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "message" => "Something went wrong , please try agai later"
            ]);
        }

        return $jsonModel;
    }

    /**
     * @OA\GET( path="/general/api/get-waste-request-type", tags={"General"},
     * @OA\Response(response="200", description="Success"),
     * @OA\Response(response="400", description="Bad Request"),
     * @OA\Response(response="401", description="Not Authorized"),
     * @OA\Response(response="403", description="Error"),
     * security={{"bearerAuth":{}}}
     * )
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function getWasteRequestTypeAction()
    {
        $jsonModel = new JsonModel();
        $response = $this->getResponse();

        try {
            $data = $this->entityManager
                ->getRepository(WasteRequestType::class)
                ->createQueryBuilder("g")
                ->select("g")
                ->getQuery()
                ->getResult(Query::HYDRATE_ARRAY);
            $jsonModel->setVariables([
                "data" => $data
            ]);
        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "message" => "Something went wrong , please try agai later"
            ]);
        }

        return $jsonModel;
    }


    /**
     * @OA\GET( path="/general/api/get-estimated-weight", tags={"General"}, description="get Estimted weight parameters",
     * @OA\Response(response="200", description="Success"),
     * @OA\Response(response="400", description="Bad Request"),
     * @OA\Response(response="401", description="Not Authorized"),
     * @OA\Response(response="403", description="Error"),
     * security={{"bearerAuth":{}}}
     * )
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function getEstimatedWeightAction()
    {
        $jsonModel = new JsonModel();
        $response = $this->getResponse();

        try {
            $data = $this->entityManager
                ->getRepository(EstimatedWeight::class)
                ->createQueryBuilder("g")
                ->select("g")
                ->getQuery()
                ->getResult(Query::HYDRATE_ARRAY);
            $jsonModel->setVariables([
                "data" => $data
            ]);
        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "message" => "Something went wrong , please try agai later"
            ]);
        }

        return $jsonModel;
    }


    /**
     * @OA\GET( path="/general/api/get-post-waste-status", tags={"General"},
     * @OA\Response(response="200", description="Success"),
     * @OA\Response(response="400", description="Bad Request"),
     * @OA\Response(response="401", description="Not Authorized"),
     * @OA\Response(response="403", description="Error"),
     * security={{"bearerAuth":{}}}
     * )
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function getPostWasteStatusAction()
    {
        $jsonModel = new JsonModel();
        $response = $this->getResponse();

        try {
            $data = $this->entityManager
                ->getRepository(PostWasteStatus::class)
                ->createQueryBuilder("p")
                ->select("p")
                ->getQuery()
                ->getResult(Query::HYDRATE_ARRAY);
            $jsonModel->setVariables([
                "data" => $data
            ]);
        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "message" => "Something went wrong , please try agai later"
            ]);
        }

        return $jsonModel;
    }


    /**
     * Used to retirve pusher real time configuration parameter
     * @OA\GET( path="/general/api/get-pusher-config", tags={"General"},
     * @OA\Response(response="200", description="Success"),
     * @OA\Response(response="400", description="Bad Request"),
     * @OA\Response(response="401", description="Not Authorized"),
     * @OA\Response(response="403", description="Error"),
     * security={{"bearerAuth":{}}}
     * )
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function getPusherConfigAction()
    {
        $jsonModel = new JsonModel();
        $response = $this->getResponse();

        try {
            $data = $this->generalService->getPusherConfig();
            $jsonModel->setVariables([
                "data" => $data[0]
            ]);
        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "message" => "Something went wrong , please try again later"
            ]);
        }

        return $jsonModel;
    }


    /**
     * Reteieves all events registered for real time communication
     * @OA\GET( path="/general/api/get-pusher-events", tags={"General"},
     * @OA\Response(response="200", description="Success"),
     * @OA\Response(response="400", description="Bad Request"),
     * @OA\Response(response="401", description="Not Authorized"),
     * @OA\Response(response="403", description="Error"),
     * security={{"bearerAuth":{}}}
     * )
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function getPusherEventsAction()
    {
        $jsonModel = new JsonModel();
        $response = $this->getResponse();

        try {
            $data = $this->generalService->getPusherEvents();
            $jsonModel->setVariables([
                "data" => $data
            ]);
        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "message" => "Something went wrong , please try again later"
            ]);
        }

        return $jsonModel;
    }


    /**
     * Reteieves all AWS credentials
     * @OA\GET( path="/general/api/get-aws-credentials", tags={"General"},
     * @OA\Response(response="200", description="Success"),
     * @OA\Response(response="400", description="Bad Request"),
     * @OA\Response(response="401", description="Not Authorized"),
     * @OA\Response(response="403", description="Error"),
     * security={{"bearerAuth":{}}}
     * )
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function getAwsCredentialsAction()
    {
        $jsonModel = new JsonModel();
        $response = $this->getResponse();

        try {
            $data = $this->entityManager
                ->getRepository(Settings::class)
                ->createQueryBuilder("p")
                ->select(["p.awsAccessKey as access_key", "p.awsSecretKey as secret_key"])
                ->getQuery()
                ->getResult(Query::HYDRATE_ARRAY);
            $jsonModel->setVariables([
                "data" => $data[0]
            ]);
        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "message" => "Something went wrong , please try again later"
            ]);
        }

        return $jsonModel;
    }


    public function getSettingsAction()
    {
        $em = $this->entityManager;
        $data = $em->getRepository(Settings::class)
            ->createQueryBuilder("s")
            ->select("s")->getQuery()
            ->getArrayResult();

        $jsonModel = new JsonModel(["data" => $data]);

        return $jsonModel;
    }


    public function getOrderStatusAction()
    {
        $jsonModel = new JsonModel();
        $em = $this->entityManager;

        $data = $em->getRepository(OrderStatus::class)->createQueryBuilder("o")->select("o")->getQuery()->getArrayResult();
        $jsonModel->setVariables([
            "data" => $data
        ]);
        return $jsonModel;
    }

    /**
     * Reteieves all Banks
     * @OA\GET( path="/general/api/get-banks", tags={"General"},
     * @OA\Response(response="200", description="Success"),
     * @OA\Response(response="400", description="Bad Request"),
     * @OA\Response(response="401", description="Not Authorized"),
     * @OA\Response(response="403", description="Error"),
     * 
     * )
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function getBanksAction()
    {
        $em = $this->entityManager;
        $data = $em->getRepository(Banks::class)
            ->createQueryBuilder("s")
            ->select("s")->getQuery()
            ->getArrayResult();

        $jsonModel = new JsonModel(["data" => $data]);

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
     * Get the value of generalService
     */
    public function getGeneralService()
    {
        return $this->generalService;
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
}
