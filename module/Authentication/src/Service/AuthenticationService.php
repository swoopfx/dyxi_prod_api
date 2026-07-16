<?php

namespace Authentication\Service;

use Authentication\Entity\User;
use Doctrine\ORM\EntityManager;
use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Authentication\AuthenticationService as LaminasAuthService;
use Laminas\Crypt\Password\Bcrypt;

class AuthenticationService implements AuthenticationServiceInterface
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
     * @var LaminasAuthService
     */
    private $authService;



    const USER_ROLE_SETUP_BROKER = 3;

    const USER_ROLE_SETUP_AGENT = 2;

    // RP user role

    const USER_ROLE_CUSTOMER = 100;

    const USER_ROLE_SCAVENGER = 125;

    const USER_ROLE_IRECYCLER = 126;

    const USER_ROLE_STAFF = 130;

    const USER_ROLE_DORI_HOST = 150;

    const USER_ROLE_TRASHBUSTER = 200;

    const USER_ROLE_HUB_SUPERVISOR = 500;

    const USER_ROLE_ADMIN = 600;

    const USER_ROLE_SUPER_USER = 1000;




    const USER_STATE_DISABLED = 2;

    const USER_STATE_ENABLED = 1;

    const USER_STATE_PENDING = 3;


    private $authenticationService;

    /**
     * Login data validation and filtration class
     *
     * @var LoginInputFilter
     */
    private $loginInputFilter;

    private $post;


    public function authenticate()
    {
        $post = $this->post;

        if ($post == null) {
            throw new \Exception("Set Post function needs to be initiated");
        }
        $inputFilter = $this->loginInputFilter;

        $inputFilter->setValidationGroup([
            "username",
            "password"
        ]);
        $inputFilter->setData($post);
    }

    public function hasIdentity()
    {
    }

    public function clearIdentity()
    {
    }

    public function getIdentity()
    {
    }

    /**
     * Static function for checking hashed password (as required by Doctrine)
     *
     * @param Authentication\Entity\User $user
     *            The identity object
     * @param string $passwordGiven
     *            Password provided to be verified
     * @return boolean true if the password was correct, else, returns false
     */
    public static function verifyHashedPassword(User $user, $passwordGiven)
    {
        $bcrypt = new Bcrypt([
            'cost' => 10
        ]);
        return $bcrypt->verify($passwordGiven, $user->getPassword());
    }

    /**
     * Undocumented function
     *
     * @param string $systemCode
     * @param string $inputedCode
     * @return boolean
     */
    public static function verifyHashedCode($systemCode, $inputedCode): bool
    {
        $bcrypt = new Bcrypt([
            "cost" => 10
        ]);
        return $bcrypt->verify($inputedCode, $systemCode);
    }

    /**
     * Encrypt Password
     *
     * Creates a Bcrypt password hash
     *
     * @return String
     */
    public static function encryptPassword($password)
    {
        // $crypt = new Bcrcomposer update
        $bcrypt = new Bcrypt([
            'cost' => 10
        ]);
        return $bcrypt->create($password);
    }




    /**
     * Get the value of authenticationService
     */
    public function getAuthenticationService()
    {
        return $this->authenticationService;
    }

    /**
     * Set the value of authenticationService
     *
     * @return  self
     */
    public function setAuthenticationService($authenticationService)
    {
        $this->authenticationService = $authenticationService;

        return $this;
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
     * Get the value of post
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * Set the value of post
     *
     * @return  self
     */
    public function setPost($post)
    {
        $this->post = $post;

        return $this;
    }
}
