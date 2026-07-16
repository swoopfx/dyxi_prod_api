<?php

namespace Authentication\Service;

use General\Service\Postmark\AuthenticationEmailService;
use Authentication\Entity\Roles;
use Authentication\Service\AuthMailtrapService;
use Ramsey\Uuid\Uuid;
use Authentication\Entity\User;
use Authentication\Entity\UserState;
use Exception;
use General\Service\GeneralService;
use Authentication\Form\InputFilter\RegisterInputfilter;
use Customer\Entity\Customer;
use Customer\Service\CustomerService;
use DateTime;
use Doctrine\ORM\EntityManager;
use General\Service\Mailtrap\MailtrapService;

class RegisterService
{
    /**
     * Undocumented variable
     *
     * @var AuthenticationEmailService;
     */
    private $postmarkAuthMailService;


    /**
     * Undocumented variable
     *
     * @var MailtrapService
     */
    private $mailtrapService;

    /**
     * Undocumented variable
     *
     * @var AuthMailtrapService
     */
    private $authmailtrapService;

    /**
     * Undocumented variable
     *
     * @var GeneralService
     */
    private $generalService;

    private $authEmailService;

    private $assignedRole = null;

    private $post;

    /**
     * Undocumented variable
     *
     * @var RegisterInputfilter
     */
    private $registerInputFilter;

    /**
     * Undocumented variable
     *
     * @var Laminas\Mvc\Controller\Plugin\Url
     */
    private $urlPlugin;

    public function register($post)
    {
        // $post = $this->post;
        /**
         * @var EntityManager
         */
        $em = $this->generalService->getEm();
        $designatedRole = null;
        if (is_null($this->assignedRole)) {
            $designatedRole = AuthenticationService::USER_ROLE_CUSTOMER;
        } else {
            $designatedRole = $this->assignedRole;
        }

        if ($post == null) {
            throw new \Exception("Post data is required");
        }
        // var_dump($post["fullname"]);

        $this->registerInputFilter->setData($post);
        $this->registerInputFilter->setValidationGroup([
            "username",
            "fullname",
            "email",
            "password",
            "confirm_password",
            "address",
            "address_google_place_id",
            "address_longitude",
            "address_latitude"

        ]);

        if ($this->registerInputFilter->isValid()) {
            // var_dump("Got here");
            $data = $this->registerInputFilter->getValues();
            $newUser = new User();

            $activationToken = uniqid(mt_rand(), true);
            $webActivationLink = $this->generateMobileCode($activationToken);

            $mobileActivationCode = self::generateMobileCode();
            $mailData = [];
            $mailData["url"] = $webActivationLink;
            $mailData["code"] = $mobileActivationCode;

            $newUser->setUsername($data["username"])
                ->setPassword(AuthenticationService::encryptPassword($data["password"]))
                ->setFullname($data["fullname"])->setEmail($data["email"])->setRole($em->find(Roles::class, $designatedRole))
                ->setCreatedOn(new \Datetime())
                ->setIsProfiled(TRUE)
                ->setEmail($data["email"])
                ->setState($em->find(UserState::class, AuthenticationService::USER_STATE_ENABLED))
                ->setRegistrationDate(new \Datetime("now"))
                ->setEmailConfirmed(false)->setIsProfiled(false)
                ->setUid(self::createUid())
                ->setMobileActivateCode($mobileActivationCode)
                ->setRegistrationToken($activationToken)
                ->setUuid(self::createUUid());

            //trigger other events

            // Register customer Entity

            $customerEntity = new Customer();
            $customerEntity->setUser($newUser)
                ->setCreatedOn(new \Datetime())
                ->setAddress($data["address"])
                ->setAddressPlaceId($data["address_google_place_id"])
                ->setAddressLatitude($data["address_latitude"])
                ->setCustomerUid(CustomerService::generateCustomerId())
                ->setCustomerUuid(CustomerService::generareCustomerUuid())
                ->setIsActive(true)

                ->setAddressLongitude($data["address_longitude"]);

            // send email
            $roleEntity = $em->find(Roles::class, $designatedRole);
            $mailData["email"] = $data["email"];
            $mailData["fullname"] = $data["fullname"];
            $mailData["role"] = $roleEntity->getName();
            // $mailData['code'] =


            if ($post["device_type"] == "mobile") {
                // $this->mobileMailNotifer($mailData);
                // $this->authmailtrapService->sendMobileVerifyCode($mailData);
                $this->postmarkAuthMailService->confirmEmailMobile($mailData);
            } else {
                $this->webMailNotifier($mailData);
            }
            $em->persist($customerEntity);
            $em->persist($newUser);
            $em->flush();
            return $data;
        } else {
            throw new \Exception(json_encode($this->registerInputFilter->getMessages()));
        }
    }

    public function confirmEmailMobile($data)
    {
        /**
         * @var EntityManager
         */
        $em = $this->generalService->getEm();
        /**
         * @var User
         */
        $userEntity = $em->getRepository(User::class)->findOneBy([
            "email" => $data["email"]
        ]);
        if ($userEntity == null) {
            throw new \Exception("Are you sure you have registered");
        } else {
            $code = $data["code"];
            if ($code == $userEntity->getMobileActivateCode()) {
                $userEntity->setUpdatedOn(new \Datetime())
                    ->setEmailConfirmed(true)
                    ->setMobileActivateCode(self::generateMobileCode());

                $em->persist($userEntity);
                $em->flush();

                $mailData["to"] = $userEntity->getEmail();

                $mailData["fullname"] = $userEntity->getFullname();
                // Send Welcome Email
                // $this->mailtrapService->welcomeEmail($mailData);
                // Send welcome email
                $this->postmarkAuthMailService->welcome($mailData);
            } else {
                throw new \Exception("Invalid Code");
            }
        }
    }

    // public function


    public static function generateMobileCode()
    {
        return mt_rand(100000, 999999);
    }

    // public static function confirm

    public function generateWebActivationLink($activationCode)
    {
        return $this->urlPlugin->fromRoute("api-auth", ["action" => "verify", "id" => $activationCode]);
    }


    private function webMailNotifier($data)
    {
        /**
         * @var AuthenticationEmailService
         */
        // $this->postmarkAuthMailService->setData($data)->confirmEmailWeb();
    }

    private function mobileMailNotifer($data)
    {
        // $this->postmarkAuthMailService->setData($data)->confirmEmailMobile();
        $this->mailtrapService->confirmEmail($data);
    }

    public static function createUUid()
    {
        $uuid = Uuid::uuid4();
        return $uuid->toString();
    }

    public static function createUid()
    {
        return uniqid("resu");
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
     * Get the value of postmarkAuthMailService
     */
    public function getPostmarkAuthMailService()
    {
        return $this->postmarkAuthMailService;
    }

    /**
     * Set the value of postmarkAuthMailService
     *
     * @return  self
     */
    public function setPostmarkAuthMailService($postmarkAuthMailService)
    {
        $this->postmarkAuthMailService = $postmarkAuthMailService;

        return $this;
    }

    /**
     * Get the value of registerInputFilter
     */
    public function getRegisterInputFilter()
    {
        return $this->registerInputFilter;
    }

    /**
     * Set the value of registerInputFilter
     *
     * @return  self
     */
    public function setRegisterInputFilter($registerInputFilter)
    {
        $this->registerInputFilter = $registerInputFilter;

        return $this;
    }

    /**
     * Get the value of assignedRole
     */
    public function getAssignedRole()
    {
        return $this->assignedRole;
    }

    /**
     * Set the value of assignedRole
     *
     * @return  self
     */
    public function setAssignedRole($assignedRole)
    {
        $this->assignedRole = $assignedRole;

        return $this;
    }

    /**
     * Get the value of urlPlugin
     */
    public function getUrlPlugin()
    {
        return $this->urlPlugin;
    }

    /**
     * Set the value of urlPlugin
     *
     * @return  self
     */
    public function setUrlPlugin($urlPlugin)
    {
        $this->urlPlugin = $urlPlugin;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  MailtrapService
     */
    public function getMailtrapService()
    {
        return $this->mailtrapService;
    }

    /**
     * Set undocumented variable
     *
     * @param  MailtrapService  $mailtrapService  Undocumented variable
     *
     * @return  self
     */
    public function setMailtrapService(MailtrapService $mailtrapService)
    {
        $this->mailtrapService = $mailtrapService;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  AuthMailtrapService
     */
    public function getAuthmailtrapService()
    {
        return $this->authmailtrapService;
    }

    /**
     * Set undocumented variable
     *
     * @param  AuthMailtrapService  $authmailtrapService  Undocumented variable
     *
     * @return  self
     */
    public function setAuthmailtrapService(AuthMailtrapService $authmailtrapService)
    {
        $this->authmailtrapService = $authmailtrapService;

        return $this;
    }
}
