<?php

namespace Customer\Service;

use Authentication\Entity\User;
use Authentication\Service\ApiAuthenticateService;
use Authentication\Service\AuthenticationService;
use Customer\Entity\Customer;
use Customer\Entity\HandshakePincode;
use Customer\Entity\NearestDorihost;
use Customer\Entity\WasteRequest;

use Customer\Entity\WasteRequestActivity;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use General\Entity\EstimatedWeight;
use General\Entity\Settings;
use General\Entity\WasteRequestState;
use General\Entity\WasteRequestType;
use General\Entity\WasteType;
use General\Service\GeneralService;
use General\Entity\WasteRequestActivityConst;
use Ramsey\Uuid\Uuid;

class CustomerService
{
    /**
     * Undocumented variable
     *
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Api Authentication Object
     *
     * @var ApiAuthenticateService
     */
    private $apiAuth;

    const WASTE_REQUEST_TYPE_PICKUP = 100;

    const WASTE_REQUEST_TYPE_DROPOFF = 200;

    const WASTE_REQUEST_TYPE_DORI_SELF_SERVICE = 300;

    const MINIMUM_SEARCH_DISTANCE = 10;


    /**
     * Undocumented function
     *
     * @param array $data
     * @return void
     */
    public function generateCustomerHandshakeEntity(array $data)
    {
        $em = $this->entityManager;
        $pin = GeneralService::generatePincode();
        $handShakePinEntity = new HandshakePincode();
        $handShakePinEntity->setPinCode($pin)
            ->setCreatedOn(new \Datetime())
            ->setGeneratedBy($em->find(User::class, $data["by"]))
            ->setGeneratedFor($em->find(User::class, $data["for"]))
            ->setWasteRequest($em->find(WasteRequest::class, $data["wid"]))
            ->setIsUsed(False);

        $em->persist($handShakePinEntity);
        $em->flush();
    }

    /**
     * used to retrieve pincode entity, specifically pincode 
     *
     * @param array $data
     * @return void
     */
    public function getCustomerHandshakeEntity(array $data)
    {
        $em = $this->entityManager;
        $data = $em->getRepository(HandshakePincode::class)
            ->createQueryBuilder("h")
            ->select("h.id as id, h.pinCode as pincode, gb.fullname as created_by, gb.username as buster_number, gf.fullname as beneficiary, w.requestUuid as waste_uuid")
            ->leftJoin("h.generatedFor", "gf")
            ->leftJoin("h.generatedBy", "gb")
            ->leftJoin("h.wasteRequest", "w")
            ->where("w.requestUuid = :wuid")
            ->andWhere("gf.id = :gfid")
            ->andWhere("h.isUsed = :isUsed")
            ->setParameters([
                "wuid" => $data["wuid"],
                "gfid" => $data["user_id"],
                "isUsed" => FALSE
            ])
            ->orderBy("h.id", "DESC")
            ->getQuery()
            ->getArrayResult();

        return $data;
    }

    public function getCustomerProfile($uuid)
    {
        $em = $this->entityManager;
        return $em->getRepository(Customer::class)
            ->createQueryBuilder("c")
            ->select(["c", "u", "r",])
            ->where("c.customerUuid = :uuid")
            ->leftJoin("c.user", "u")
            ->leftJoin("u.role", "r")
            ->setParameters([
                "uuid" => $uuid
            ])
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);
    }


    public function dropoffWasteService($data)
    {
        // var_dump($data);
        $activeUser = $this->apiAuth->getContainerIdentity();
        $entityManager = $this->entityManager;
        $userEntity = $entityManager->getRepository(User::class)->findOneBy([
            "uuid" => $activeUser["uuid"]
        ]);
        $wasteRequestEntity = new WasteRequest();
        $date = new \Datetime();
        // $expiredDatetimeObject = $date->setTimeStamp($expiredData["expDate"]);
        // 
        $dropOffDataAndTime = \DateTime::createFromFormat("Y-m-d H:i", $data["datetime_of_drop_off"]);
        $wasteRequestEntity->setCreatedOn(new \DateTime())
            ->setHandshakeCode(self::generateHandshakeCode())
            ->setIsActive(true)
            ->setRequestId(self::generateRequestId())
            ->setUser($userEntity)
            ->setRequestUuid(self::generateRequestUuid())
            ->setDropOffhost($entityManager->find(User::class, $data["drop_off_host"]))
            ->setWasteType($entityManager->find(WasteType::class, $data["waste_type"]))
            ->setRequestType($entityManager->find(WasteRequestType::class, $data["request_type"]))
            ->setEstimatedWeight($entityManager->find(EstimatedWeight::class, $data['estimated_weight']))
            ->setIsConfirmedHandshake(false)
            ->setWasteRequestState($entityManager->find(WasteRequestState::class, GeneralService::WASTE_REQUEST_STATE_INITIATED))
            ->setRequestDatetime(new \Datetime()) // Date and time for the delivery
            // ->setDropOffhost($data[""])
            ->setRequestId(self::generateRequestId());


        $wasteRequestActivityEntity = new WasteRequestActivity();
        $wasteRequestActivityEntity->setCreatedOn(new \Datetime())
            ->setInititor($userEntity)
            ->setActivity($entityManager->find(WasteRequestActivityConst::class, GeneralService::REQUEST_ACTIVITY_DROP_OFF_REQUESTED))
            ->setDescription("{$userEntity->getFullname()} has intiated a Dropoff")
            ->setWasteRequest($wasteRequestEntity);

        $entityManager->persist($wasteRequestActivityEntity);
        $entityManager->persist($wasteRequestEntity);
        $entityManager->flush();

        $wasteType = $entityManager->find(WasteType::class, $data["waste_type"]);
        $dropOffHost = $entityManager->find(User::class, $data["drop_off_host"]);
        // send email
        return [
            "waste_type" => $wasteType->getType(),
            "drop_off_host_name" => $dropOffHost->getFullname(),
            "drop_off_host_address" => $dropOffHost->getCustomer()->getAddress()
        ];
    }

    public function pickupWasteService($data)
    {
        $entityManager = $this->entityManager;
        // var_dump($entityManager->find(Settings::class, 100));
        $activeUser = $this->apiAuth->getContainerIdentity();

        $userEntity = $entityManager->getRepository(User::class)->findOneBy([
            "uuid" => $activeUser["uuid"]
        ]);
        $wasteRequestEntity = new WasteRequest();
        $date = new \Datetime();
        // $expiredDatetimeObject = $date->setTimeStamp($expiredData["expDate"]);
        $pickupDataAndTime = \DateTime::createFromFormat("Y-m-d H:i", $data["datetime_of_pickup"]);
        // var_dump($pickupDataAndTime);
        $wasteRequestEntity->setCreatedOn(new \DateTime())
            ->setHandshakeCode(self::generateHandshakeCode())
            ->setIsActive(true)
            ->setRequestId(self::generateRequestId())
            ->setRequestUuid(self::generateRequestUuid())
            ->setWasteType($entityManager->find(WasteType::class, $data["waste_type"]))
            ->setRequestType($entityManager->find(WasteRequestType::class, $data["request_type"]))
            ->setEstimatedWeight($entityManager->find(EstimatedWeight::class, $data['estimated_weight']))
            ->setIsConfirmedHandshake(false)
            ->setRequestDatetime($pickupDataAndTime)
            ->setPickupAddress($data["pickup_address"])
            ->setWasteRequestState($entityManager->find(WasteRequestState::class, GeneralService::WASTE_REQUEST_STATE_INITIATED))
            ->setNote($data["note"])
            ->setLongitude($data["address_longitude"])
            ->setLatitude($data["address_latitude"])
            ->setPickupPlaceId($data["address_google_place_id"])
            ->setUser($userEntity);

        $wasteRequestActivityEntity = new WasteRequestActivity();
        $wasteRequestActivityEntity->setCreatedOn(new \Datetime())
            ->setInititor($userEntity)
            ->setActivity($entityManager->find(WasteRequestActivityConst::class, GeneralService::REQUEST_ACTIVITY_PICKUP_REQUESTED))
            ->setDescription("{$userEntity->getFullname()} has intiated a Pick up ")
            ->setWasteRequest($wasteRequestEntity);

        $entityManager->persist($wasteRequestActivityEntity);
        $entityManager->persist($wasteRequestEntity);
        $entityManager->flush();
    }

    public static function calculateCustomerCredit($data)
    {
        $pricePerKg = $data["kg_price"];
        $totalWeightInKg = $data["weight"];
        return $pricePerKg * $totalWeightInKg * 0.95;
    }


    public function searchNearestHost($distance = self::MINIMUM_SEARCH_DISTANCE)
    {
        $em = $this->entityManager;
        $activeUser = $this->apiAuth->getContainerIdentity();
        $userEntity = $em->getRepository(User::class)->findOneBy([
            "uuid" => $activeUser["uuid"]
        ]);
        /**
         * @var Customer
         *
         */
        $customerEntity = $em->getRepository(Customer::class)->findOneBy([
            "user" => $userEntity->getId()
        ]);
        $availahostHost = $em->getRepository(NearestDorihost::class)->findBy([
            "customer" => $userEntity->getId()
        ]);
        foreach ($availahostHost as $host) {
            $em->remove($host);
            $em->flush();
        }
        $activeDoriHost = $em->getRepository(Customer::class)
            ->createQueryBuilder("c")
            ->select(["c", "u", "r"])
            ->leftJoin("c.user", "u")
            ->leftJoin("u.role", "r")
            ->where("r.id = :roleId")
            // ->andWhere("u.uuid != :uuid")
            // ->andWhere("u.isActive = :active")
            ->andWhere("c.isActive = :cactive")
            ->setParameters([
                // "uuid" => $activeUser["uuid"],
                "cactive" => true,
                // "active" => true,
                "roleId" => AuthenticationService::USER_ROLE_DORI_HOST
            ])->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);

        $lat1 = $customerEntity->getAddressLatitude(); // $data["lat"];

        $lon1 = $customerEntity->getAddressLongitude(); //$data["lon"];

        $closerHost = [];
        do {
            foreach ($activeDoriHost as $index => $datax) {

                // var_dump($datax);
                $dist =  $this->haversineGreatCircleDistance($lat1, $lon1, $datax["addressLatitude"], $datax["addressLongitude"]);
                // var_dump($datax);
                // $lat2 = $datax["addressLatitude"];
                // $lon2 = $datax["addressLongitude"];

                // $latFrom = deg2rad($);
                // $lonFrom = deg2rad($longitudeFrom);
                // $latTo = deg2rad($latitudeTo);
                // $lonTo = deg2rad($longitudeTo);

                // $theta = $lon1 - $lon2;
                // $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
                // $dist = acos($dist);
                // $dist = rad2deg($dist);
                // $miles = $dist * 60 * 1.1515;
                // $km = $miles * 1.609344; // Kilometers
                // $nm = $miles * 0.8684; //Nautical Miles
                // var_dump($km);

                $km = $dist / 1000;
                // var_dump($dist);

                // var_dump($km);
                // print($km);
                // print("OK");
                // var_dump($datax["address"]);
                // var_dump($km);

                if ($km < $distance) {
                    $closerHost[$index] = $datax;
                    // var_dump($datax);
                    // if(count($closerHost) > 0)
                    // clear all nearest host related to this user
                    // if (count($closerHost) > 0) {


                    $nearestHost = new NearestDorihost();
                    $nearestHost->setCreatedOn(new \Datetime())
                        ->setCustomer($userEntity)
                        ->setDorihost($em->find(User::class, $datax["user"]["id"]))->setDistanceAway($km);

                    $em->persist($nearestHost);
                    $em->flush();
                    // return true;
                }


                // $unit = strtoupper($unit);

                // if ($unit == "K") {
                //     return ($miles * 1.609344);
                // } else if ($unit == "N") {
                //     return ($miles * 0.8684);
                // } else {
                //     return $miles;
                // }
            }
        } while ($closerHost == 0 && $distance < self::MINIMUM_SEARCH_DISTANCE);
    }

    /**
     * Calculates the great-circle distance between two points, with
     * the Haversine formula.
     * @param float $latitudeFrom Latitude of start point in [deg decimal]
     * @param float $longitudeFrom Longitude of start point in [deg decimal]
     * @param float $latitudeTo Latitude of target point in [deg decimal]
     * @param float $longitudeTo Longitude of target point in [deg decimal]
     * @param float $earthRadius Mean earth radius in [m]
     * @return float Distance between points in [m] (same as earthRadius)
     */
    private function haversineGreatCircleDistance(
        $latitudeFrom,
        $longitudeFrom,
        $latitudeTo,
        $longitudeTo,
        $earthRadius = 6371000
    ) {
        // // convert from degrees to radians
        // $latFrom = deg2rad($latitudeFrom);
        // $lonFrom = deg2rad($longitudeFrom);
        // $latTo = deg2rad($latitudeTo);
        // $lonTo = deg2rad($longitudeTo);

        // $latDelta = $latTo - $latFrom;
        // $lonDelta = $lonTo - $lonFrom;

        // $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
        //     cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        // return $angle * $earthRadius;

        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $lonDelta = $lonTo - $lonFrom;
        $a = pow(cos($latTo) * sin($lonDelta), 2) +
            pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

        $angle = atan2(sqrt($a), $b);
        return $angle * $earthRadius;
    }


    public function getNearestHost()
    {
        $em = $this->entityManager;
        $activeUser = $this->apiAuth->getContainerIdentity();
        $userEntity = $em->getRepository(User::class)->findOneBy([
            "uuid" => $activeUser["uuid"]
        ]);

        // $availahostHost = $em->getRepository(NearestDorihost::class)->findBy([
        //     "customer" => $userEntity->getId()
        // ]);

        $nearestHostEntity = $em->getRepository(NearestDorihost::class)
            ->createQueryBuilder("n")
            ->select([
                "partial n.{id, customer, dorihost, distanceAway}",
                "partial c.{id, fullname, email}",
                "partial h.{id, fullname, email, username}",
                "partial r.{id, name}",
                "partial hc.{id, address, addressLongitude, addressLatitude}"
            ])
            ->leftJoin("n.customer", "c")
            // ->leftJoin("n.user", "h")
            ->leftJoin("n.dorihost", "h")
            ->leftJoin("h.role", "r")
            ->leftJoin("h.customer", "hc")
            ->where("r.id = :role")
            ->andWhere("c.id = :userId")
            // ->andWhere("h.isActive = :active")
            ->setParameters([
                // "active" => true,
                "role" => AuthenticationService::USER_ROLE_DORI_HOST,
                "userId" => $userEntity->getId()
            ])
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);

        return $nearestHostEntity;
    }






    public function sendCustomerEmail() {}



    public static function generateRequestId()
    {
        return uniqid("req");
    }

    public static function generateRequestUuid()
    {
        $id = Uuid::uuid4();
        return $id->toString();
    }


    public static function generateHandshakeCode()
    {
        $id = Uuid::uuid4();
        return $id->toString();
    }


    public static function generateCustomerId()
    {
        return uniqid("cust");
    }


    public static function generareCustomerUuid()
    {
        $u = Uuid::uuid4();
        return $u->toString();
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
     * Set the value of apiAuth
     *
     * @return  self
     */
    public function setApiAuth($apiAuth)
    {
        $this->apiAuth = $apiAuth;

        return $this;
    }
}
