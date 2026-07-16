<?php

namespace General\Service;

use Authentication\Service\ApiAuthenticateService;
// use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\Query;
use General\Entity\Settings;
use Doctrine\ORM\EntityManager;
use General\Entity\PusherEvents;

class GeneralService
{
    /**
     * Undocumented variable
     *
     * @var EntityManager
     */
    private $em;

    /**
     * Undocumented variable
     *
     * @var 
     */
    private  $objectManager;

    private $mail;

    private $authService;

    /**
     *
     *
     * @var ApiAuthenticateService
     */
    private $apiAuthService;

    private $settings;

    const APP_NAME = "Recycle Point Manager";

    const MAX_PAGE_COUNT = 40;

    const EMAIL_NOTIFiER = "no-reply@recyclepoints.com";
    const COMPANY_NAME = "Recycle Points";
    const COMPANY_ADDRESS = "Plot 102, Lagos Street, Off Cemetry Street, Lagos , NIgeria";
    const COMPANY_URL = "www.recyclepoints.com/";

    const MAILTRAP_LIVE_URL = "https://send.api.mailtrap.io/api/send";


    const ESTIMATED_WEIGHT_LESS_THAN_50 = 100;

    const ESTIMATED_WEIGHT_MORE_THAN_50_LESS_THAN_150 = 200;

    const ESTIMATED_WEIGHT_MORE_THAN_150 = 300;


    const WASTE_REQUEST_STATE_INITIATED = 100;

    const WASTE_REQUEST_STATE_COMPLETED = 1000;

    const WASTE_REQUEST_STATE_PROCESSING = 500;

    const WASTE_REQUEST_STATE_CANCELED = 2000;

    const WASTE_COLLECTION_TYPE_WPI = 100;

    const WASTE_COLLECTION_TYPE_IRECYCLE = 200;

    const REQUEST_ACTIVITY_INITIATED = 1000;

    const REQUEST_ACTIVITY_ASSIGNED = 2000;

    const REQUEST_ACTIVITY_ASSIGNEE_ACCEPTED = 3000;

    const REQUEST_ACTIVITY_ASSIGNEE_DECLINED = 3100;

    const REQUEST_ACTIVITY_UPDATED = 4000;

    const REQUEST_ACTIVITY_HANDSHAKE_INITITAED = 5000;

    const REQUEST_ACTIVITY_HANDSHAKE_CONFIMRED = 6000;



    const REQUEST_ACTIVITY_PICKUP_REQUESTED = 7000;

    const REQUEST_ACTIVITY_DROP_OFF_REQUESTED = 7100;

    // const REQUEST_ACTIVITY_BUSTER

    const REQUEST_ACTIVITY_PROCESSING = 20000;


    const CASHOUT_REQUEST_STATUS_INITITATED = 100;

    const CASHOUT_REQUEST_STATUS_COMPLETED = 500;

    const CASHOUT_REQUEST_STATUS_FAILED = 200;

    const TRIGGER_LOG_WASTE = "log_waste";

    const TRIGGER_LOG_DUMP = "log_dump";

    // const WASTE_REQUEST_STATE_


    public static function generatePincode(){
        return rand(0,9).rand(0,9).rand(0,9).rand(0,9);
    }


    // const SERVICE_GENERAL_SERVICE = "general_"

    public function getPusherConfig()
    {
        return  $this->em
            ->getRepository(Settings::class)
            ->createQueryBuilder("g")
            ->select([
                "g.pusherAppKey as pusher_app_key",
                "g.pusherSecretKey as pusher_secret_key",
                "g.pusherAppId as pusher_app_id",
                "g.pusherAppCluster as pusher_cluster",
                "g.pusherChannel as pusher_channel"
            ])
            ->where("g.id = :id")
            ->setParameters([
                "id" => 100
            ])
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);
    }

    public function getPusherEvents()
    {
        return $this->em->getRepository(PusherEvents::class)->createQueryBuilder("p")->getQuery()->getResult(Query::HYDRATE_ARRAY);
    }


    public static function generateKey($cipher)
    {
        return random_bytes($cipher === 'AES-128-CBC' ? 16 : 32);
    }


    /**
     * Get the value of em
     */
    public function getEm()
    {
        return $this->em;
    }

    /**
     * Set the value of em
     *
     * @return  self
     */
    public function setEm($em)
    {
        $this->em = $em;

        return $this;
    }

    /**
     * Get the value of mail
     */
    public function getMail()
    {
        return $this->mail;
    }

    /**
     * Set the value of mail
     *
     * @return  self
     */
    public function setMail($mail)
    {
        $this->mail = $mail;

        return $this;
    }

    /**
     * Get the value of authService
     */
    public function getAuthService()
    {
        return $this->authService;
    }

    /**
     * Set the value of authService
     *
     * @return  self
     */
    public function setAuthService($authService)
    {
        $this->authService = $authService;

        return $this;
    }

    /**
     * Get the value of settings
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Set the value of settings
     *
     * @return  self
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;

        return $this;
    }



    /**
     * Get the value of apiAuthService
     *
     * @return  ApiAuthenticateService
     */
    public function getApiAuthService()
    {
        return $this->apiAuthService;
    }

    /**
     * Set the value of apiAuthService
     *
     * @param  ApiAuthenticateService  $apiAuthService
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
     * @retur  
     */ 
    public function getObjectManager()
    {
        return $this->objectManager;
    }

    /**
     * Set undocumented variable
     *
     * @param 
     *
     * @return  self
     */ 
    public function setObjectManager($objectManager)
    {
        $this->objectManager = $objectManager;

        return $this;
    }
}
