<?php

namespace Authentication\Service;

use Authentication\Entity\User;
use Authentication\Entity\UserRefreshToken;
use Authentication\Exceptions\EmptyTokenException;
use Authentication\Exceptions\ExpiredAuthDateException;
use Authentication\Exceptions\InvalidTokenException;
use Laminas\InputFilter\InputFilter;
use Laminas\Json\Json;
use Authentication\Service\JWTIssuer;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Authentication\Form\InputFilter\RegisterInputfilter;
use Authentication\Form\InputFilter\LoginInputFilter;
use Doctrine\ORM\EntityManager;
use Exception;
use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Http\Header\SetCookie;
use Laminas\Session\Container;
use Ramsey\Uuid\Uuid;
use Wallet\Service\WalletApiService;
use General\Service\Postmark\AuthenticationEmailService as AuthPostMarkService;
use RuntimeException;

class ApiAuthenticateService implements AuthenticationServiceInterface
{
    /**
     * @var JWTIssuer
     */
    private $jwtIssuer;

    // private $request;

    // private $response;

    /**
     * Register data Validation and filtration calss
     *
     * @var RegisterInputfilter
     */
    private $registerInputFilter;

    /**
     * Login data validation and filtration class
     *
     * @var LoginInputFilter
     */
    private $loginInputFilter;

    /**
     *
     *
     * @var EntityManager
     */
    private $entityManager;

    private $systemConfig;

    const COOKIE_NAME = "auth";

    private $post;


    /**
     * Http Request Object
     *
     * @var Request
     */
    private $requestObject;

    /**
     * Http Response Object
     *
     * @var Response
     */
    private $responseObject;

    /**
     * Undocumented variable
     *
     * @var
     */
    private $authenticationService;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    private $authEmailService;

    /**
     * Undocumented variable
     *
     * @var WalletApiService
     */
    private $walletService;


    public function getBearerToken()
    {
        $requestObject = $this->requestObject;

        if (!$requestObject->getHeader('Authorization')) {
            throw new EmptyTokenException("Token absent");
        } else {
            $authorizationHeader = $requestObject->getHeader('Authorization')->getFieldValue();

            // HEADER: Get the access token from the header
            if (!empty($authorizationHeader)) {
                if (preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
                    return $matches[1];
                } else {
                    throw new InvalidTokenException("Improper Bearer format");
                }
            }
        }
    }


    /**
     * Authenticate against username and password
     *
     * @return array
     */
    public function authenticate()
    {
        $post = $this->post;
        if ($post == null) {
            throw new Exception("Set Post function needs to be initiated");
        }
        $inputFilter = $this->loginInputFilter;

        $inputFilter->setValidationGroup([
            "user_agent",
            "user_ip",
            "username",
            "password"
        ]);
        $inputFilter->setData($post);

        if ($inputFilter->isValid()) {
            $data = $inputFilter->getValues();
            $authService = $this->authenticationService;
            $adapter = $authService->getAdapter();
            $phoneOrEmail = $data["username"];

            $errorMessageContainer = new Container("error_code");
            $errorMessageContainer->code = 400;

            $em = $this->entityManager;
            $user = $em->createQuery("SELECT u FROM Authentication\Entity\User u WHERE u.email = :phoneOrEmail OR u.username = :phoneOrEmail")
                ->setParameter('phoneOrEmail', $phoneOrEmail)
                ->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);

            if (count($user) == 0) {
                throw new \Exception("Invalid Credentials");
            }

            /**
             * @var User
             */
            $user = $user[0];

            if (!$user->getEmailConfirmed() == 1) {
                $errorMessageContainer->code = 437;
                throw new \Exception("You are yet to confirm your email! please go to the registered email to confirm your account");
               
            }
            if ($user->getState()->getId() != 1) {
                $errorMessageContainer->code = 419;
                throw new \Exception("Your account is disabled");
               
            }


            $adapter->setIdentity($user->getEmail());
            $adapter->setCredential($data["password"]);
            // $adapter->setIdentityValue($user->getEmail());
            // $adapter->setCredentialValue($data['password']);

            $authResult = $authService->authenticate();

            if ($authResult->isValid()) {
                $identity = $authResult->getIdentity();
                $authService->getStorage()->write($identity);
                $uuid = Uuid::uuid4();
                // generate jwt token
                $refresh_uid = uniqid("rt", true); // token to refresh the access token

                $data_r = [
                    "uuid" => $user->getUuid(),
                    "uid" => $user->getUid(),
                    "aud" => $uuid,
                    "email" => $phoneOrEmail,
                    "role" => $user->getRole()->getId(),
                    "token_id" => self::generateTokenId(),
                ];

                $data_r["token"] = $this->jwtIssuer->issueToken($data_r)->toString();
                $data_r["userid"] = $user->getId();
                $data_r["expire"] = 1800; // fix expiry date
                $data_r["u_uid"] = $user->getUid();
                $data_r["refresh_uid"] = $refresh_uid;

                $data['fullname'] = $user->getFullname();
                $data["email"] = $user->getEmail();
                $data["uuid"] = $user->getUuid();
                $data["username"] = $user->getUsername();
                $data["role"] = $user->getRole()->getName();
                $data["role_id"] = $user->getRole()->getId();
                $data["wallet"] = $user->getWallet() == null ? 0 : $user->getWallet()->getBalance();
                $data["profile_pic"] = $user->getProfilePic();


                // var_dump($data["user_agent"]);
                // Generate refresh token
                // Store in database
                // store in header cookie httponly settings
                $refreshData = [];
                $refreshData["ip"] = $data["user_ip"];
                $refreshData["data"] = $data_r;
                $refreshData["user_agent"] = $data["user_agent"];
                $refreshData["refresh_uid"] = $refresh_uid;
                $refreshData["uid"] = $data_r["uid"];
                $refreshData["user_id"] = $user->getId();

                $longLived = isset($post['remember_me']) && (bool)$post['remember_me'];
                $refreshToken = $this->jwtIssuer->generateRefreshToken($refreshData, $longLived);
                $cookie = new SetCookie(self::COOKIE_NAME);

                $cookie->setValue($refreshToken);
                $cookie->setExpires($longLived ? (60 * 60 * 24 * 90) : (60 * 60 * 24 * 30));
                $cookie->setPath("/");
                $cookie->setSecure(true);
                $cookie->setHttponly(true);
                $config = $this->jwtIssuer->getSystemConfig();
                $cookie->setDomain($config["jwt"]["url"]);

                $data["cookie"] = $cookie;

                $result = array_merge($data, $data_r);
                $result["refresh_token"] = $refreshToken; // also returned in JSON body for API clients

                return $result;
            } else {
                throw new \Exception(Json::encode("Invalid Credentials"));
            }
        } else {
            throw new \Exception(Json::encode($inputFilter->getMessages()));
        }
    }

    public function generateRefreshToken($refreshEntity, $user)
    {
        $jwt = $refreshEntity->getRefreshToken();
        $jwtIssuer = $this->jwtIssuer;
        $jwtIssuer->validateRefreshToken($jwt);
        // Validate Refresh Token
        // if it is valid
        // Generate a new access token and refersh token;
        // $access_token = $jwtIssuer->
        $uuid = Uuid::uuid4();
        $refresh_uid = uniqid("rt", true);
        $data_r = [
            "uuid" => $user->getUuid(),
            "uid" => $user->getUid(),
            "aud" => $uuid,
            "email" => $user->getEmail(),
            "role" => $user->getRole()->getId(),
            "token_id" => self::generateTokenId(),
        ];

        $data_r["token"] = $this->jwtIssuer->issueToken($data_r)->toString();
        $data_r["userid"] = $user->getId();
        $data_r["expire"] = 1800; // fix expiry date
        $data_r["u_uid"] = $user->getUid();
        $data_r["refresh_uid"] = $refresh_uid;


        $refreshData = [];
        // $refreshData["ip"] = $data["user_ip"];
        $refreshData["data"] = $data_r;
        // $refreshData["user_agent"] = $data["user_agent"];
        $refreshData["refresh_uid"] = $refresh_uid;
        $refreshData["uid"] = $data_r["uid"];
        $refreshData["user_id"] = $user->getId();

        $refreshToken = $this->jwtIssuer->generateRefreshToken($refreshData);
        $this->invalidateRefreshToken($refreshEntity);
        // deactivate the previous one

        return $data_r;
    }

    public function invalidateRefreshToken(UserRefreshToken $entity)
    {
        $em = $this->entityManager;
        $entity->setTokenId(self::generateTokenId() . "-invalid")
            ->setRefreshToken("revoked");

        $em->persist($entity);
        $em->flush();
    }

    /**
     * Exchange a refresh token for a new access token + rotated refresh token.
     * The old refresh token record is invalidated immediately (token rotation).
     *
     * @param  string  $refreshToken  JWT refresh token string
     * @return array
     */
    public function exchangeRefreshToken(string $refreshToken): array
    {
        $jwtIssuer = $this->jwtIssuer;
        $em        = $this->entityManager;

        // 1. Verify the refresh token signature and expiry
        $token  = $jwtIssuer->validateRefreshToken($refreshToken);
        $claims = $token->claims();
        $tokenId = $claims->get('jti'); // token_id stored as jti

        // 2. Look up the matching DB record to ensure it has not already been rotated/revoked
        $refreshEntity = $em->getRepository(UserRefreshToken::class)->findOneBy(['tokenId' => $tokenId]);
        if (!$refreshEntity) {
            throw new \Exception('Refresh token has been revoked or does not exist');
        }

        // 3. Check record expiry (belt-and-suspenders alongside JWT expiry)
        if ($refreshEntity->getExpiresOn() < new \DateTime()) {
            throw new \Exception('Refresh token has expired');
        }

        // 4. Load the associated user
        /** @var User $user */
        $user = $refreshEntity->getUserId();
        if (!$user || $user->getState()->getId() != 1) {
            throw new \Exception('User account is disabled or not found');
        }

        // 5. Build new access token
        $uuid        = \Ramsey\Uuid\Uuid::uuid4();
        $refresh_uid = uniqid('rt', true);
        $data_r = [
            'uuid'     => $user->getUuid(),
            'uid'      => $user->getUid(),
            'aud'      => $uuid,
            'email'    => $user->getEmail(),
            'role'     => $user->getRole()->getId(),
            'token_id' => self::generateTokenId(),
        ];
        $data_r['token']       = $jwtIssuer->issueToken($data_r)->toString();
        $data_r['userid']      = $user->getId();
        $data_r['expire']      = 1800;
        $data_r['u_uid']       = $user->getUid();
        $data_r['refresh_uid'] = $refresh_uid;

        // 6. Issue new refresh token (rotation)
        $refreshData = [
            'data'        => $data_r,
            'refresh_uid' => $refresh_uid,
            'uid'         => $data_r['uid'],
            'user_id'     => $user->getId(),
        ];
        $newRefreshToken = $jwtIssuer->generateRefreshToken($refreshData);

        // 7. Invalidate the old refresh token record
        $this->invalidateRefreshToken($refreshEntity);

        // 8. Build response
        $data = [
            'fullname' => $user->getFullname(),
            'email'    => $user->getEmail(),
            'uuid'     => $user->getUuid(),
            'username' => $user->getUsername(),
            'role'     => $user->getRole()->getName(),
            'role_id'  => $user->getRole()->getId(),
            'wallet'   => $user->getWallet() == null ? 0 : $user->getWallet()->getBalance(),
            'profile_pic' => $user->getProfilePic(),
        ];

        $cookie = new SetCookie(self::COOKIE_NAME);
        $cookie->setValue($newRefreshToken);
        $cookie->setExpires(60 * 60 * 24 * 30);
        $cookie->setPath('/');
        $cookie->setSecure(true);
        $cookie->setHttponly(true);
        $config = $jwtIssuer->getSystemConfig();
        $cookie->setDomain($config['jwt']['url']);

        $data['cookie']        = $cookie;
        $data['refresh_token'] = $newRefreshToken;

        return array_merge($data, $data_r);
    }

    /**
     * Revoke a refresh token (logout).
     *
     * @param  string  $refreshToken  JWT refresh token string
     */
    public function revokeRefreshToken(string $refreshToken): void
    {
        $jwtIssuer = $this->jwtIssuer;
        $em        = $this->entityManager;

        // Parse without strict expiry check so even an expired token can be revoked
        $token   = $jwtIssuer->validateRefreshToken($refreshToken);
        $tokenId = $token->claims()->get('jti');

        $refreshEntity = $em->getRepository(UserRefreshToken::class)->findOneBy(['tokenId' => $tokenId]);
        if ($refreshEntity) {
            $this->invalidateRefreshToken($refreshEntity);
        }
        // If already revoked / not found, treat logout as a no-op (idempotent)
    }


    /**
     * Generates a token IDentity
     *
     * @return void
     */
    private static function generateTokenId()
    {
        $uuid = Uuid::uuid4();
        return $uuid->toString();
    }

    public function hasIdentity()
    {
        try {
            if ($this->getIdentity() instanceof \Exception) {
                return false;
            } else {
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function isTokenValid()
    {

        $token = $this->getBearerToken();
        if ($this->jwtIssuer->validateToken($token) instanceof \Exception) {
            return false;
        } else {
            return true;
        }
    }


    public function getIdentity()
    {

        $jwt = null;
        // try {

        $jwt = $this->getBearerToken();
        // } catch (\Throwable $th) {
        //     throw new \Exception("No way");
        // }
        $jwtServe = $this->jwtIssuer;

        // parse token


        // try {
        $expiredData = $jwtServe->retreiveTokenData($jwt);
        $date = new \Datetime();
        // $expiredDatetimeObject = $date->setTimeStamp($expiredData["expDate"]);
        // $nowDate = new \Datetime("now");
        // var_dump($expiredData["expDate"]);
        //  var_dump($expiredData["issDate"]);
        // var_dump($nowDate);
        if ($date > $expiredData["expDate"]) {
            throw new ExpiredAuthDateException("token expired");
        }

        $token = $jwtServe->validateToken($jwt);
        // } catch (\Throwable $th) {



        // }

        if ($token == null) {
            throw new EmptyTokenException("This token is Empty");
        } else {
            $data = $token->claims()->get("coded");
            $container = new Container("identity");
            // $container->identify = $data[""];
            return $data;
        }
    }

    public function refreshTokenIdentity()
    {
        $jwt = null;
        // try {

        $jwt = $this->getBearerToken();
        // } catch (\Throwable $th) {
        //     throw new \Exception("No way");
        // }
        $jwtServe = $this->jwtIssuer;
        $token = $jwtServe->expiredValidateToken($jwt);
        // $expiredData = $jwtServe->retreiveTokenData($jwt);
        if ($token == null) {
            throw new EmptyTokenException("This token is Empty");
        } else {
            $data = $token->claims()->get("coded");
            // $container = new Container("identity");
            // $container->identify = $data[""];
            return $data;
        }
    }

    public function setContainerIdentity($claims)
    {
        $identityContainer = new Container("api_identity");
        $identityContainer->ide = $claims;
        return $this;
    }

    public function getContainerIdentity()
    {
        $identityContainer = new Container("api_identity");
        return $identityContainer->ide;
    }



    public function clearIdentity()
    {
        return "";
    }



    public function register($post)
    {
        $inputFilter = $this->registerInputFilter;
        $inputFilter->setData($post);
        if ($inputFilter->isValid()) {
            // Extract Data =
            // Post Data into Database
            // Send mail notification
        } else {
            throw new \Exception(Json::encode($inputFilter->getMessages()));
        }
    }

    public function refreshToken()
    {
        if (!$this->hasCookie()) {
            // throw new RuntimeException("Http Cookie Absent");

            // ceck for refresh uid
            // check for a user id
        }
        $post = $this->post;
        $cookie = $this->readCookie();

        // Search for token  in UserRefresh Token table by  user device and IP
        /*
         * Just to make sure the same device is refreshing the token
         * if it exist, check if it is still valid
         *
         *
         */
    }


    private function hasCookie()
    {
        if (!($this->requestObject instanceof Request)) {
            return false;
        }

        return $this->requestObject->getHeaders()->has('Cookie') && $this->request->getCookie()->offsetExists(self::COOKIE_NAME);
    }

    private function readCookie()
    {
        if (!($this->requestObject instanceof Request)) {
            return null;
        }

        return $this->requestObject->getCookie()->offsetGet(self::COOKIE_NAME);
    }



    public function generate($claim)
    {
        $jwtIssuer = $this->jwtIssuer;

        if ($jwtIssuer instanceof JWTIssuer) {
            return $jwtIssuer->issueToken($claim)->toString();
        }
    }

    public function validate($jwt)
    {
        return $this->jwtIssuer->parseToken($jwt);
    }

    /**
     * Get register data Validation and filtration calss
     *
     * @return  RegisterInputfilter
     */
    public function getRegisterInputFilter()
    {
        return $this->registerInputFilter;
    }

    /**
     * Set register data Validation and filtration calss
     *
     * @param  RegisterInputfilter  $registerInputFilter  Register data Validation and filtration calss
     *
     * @return  self
     */
    public function setRegisterInputFilter(RegisterInputfilter $registerInputFilter)
    {
        $this->registerInputFilter = $registerInputFilter;

        return $this;
    }

    /**
     * Get login data validation and filtration class
     *
     * @return  LoginInputFilter
     */
    public function getLoginInputFilter()
    {
        return $this->loginInputFilter;
    }

    /**
     * Set login data validation and filtration class
     *
     * @param  LoginInputFilter  $loginInputFilter  Login data validation and filtration class
     *
     * @return  self
     */
    public function setLoginInputFilter(LoginInputFilter $loginInputFilter)
    {
        $this->loginInputFilter = $loginInputFilter;

        return $this;
    }

    /**
     * Get the value of entityManager
     *
     * @return  EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * Set the value of entityManager
     *
     * @param  EntityManager  $entityManager
     *
     * @return  self
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;

        return $this;
    }

    /**
     * Get the value of jwtIssuer
     *
     * @return  JWTIssuer
     */
    public function getJwtIssuer()
    {
        return $this->jwtIssuer;
    }

    /**
     * Set the value of jwtIssuer
     *
     * @param  JWTIssuer  $jwtIssuer
     *
     * @return  self
     */
    public function setJwtIssuer(JWTIssuer $jwtIssuer)
    {
        $this->jwtIssuer = $jwtIssuer;

        return $this;
    }

    /**
     * Get the value of systemConfig
     */
    public function getSystemConfig()
    {
        return $this->systemConfig;
    }

    /**
     * Set the value of systemConfig
     *
     * @return  self
     */
    public function setSystemConfig($systemConfig)
    {
        $this->systemConfig = $systemConfig;

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

    /**
     * Get http Request Object
     *
     * @return  Request
     */
    public function getRequestObject()
    {
        return $this->requestObject;
    }

    /**
     * Set http Request Object
     *
     * @param  Request  $requestObject  Http Request Object
     *
     * @return  self
     */
    public function setRequestObject(Request $requestObject)
    {
        $this->requestObject = $requestObject;

        return $this;
    }

    /**
     * Get http Response Object
     *
     * @return  Response
     */
    public function getResponseObject()
    {
        return $this->responseObject;
    }

    /**
     * Set http Response Object
     *
     * @param  Response  $responseObject  Http Response Object
     *
     * @return  self
     */
    public function setResponseObject(Response $responseObject)
    {
        $this->responseObject = $responseObject;

        return $this;
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
     * @return  WalletApiService
     */
    public function getWalletService()
    {
        return $this->walletService;
    }

    /**
     * Set undocumented variable
     *
     * @param  WalletApiService  $walletService  Undocumented variable
     *
     * @return  self
     */
    public function setWalletService(WalletApiService $walletService)
    {
        $this->walletService = $walletService;

        return $this;
    }

    public function authenticateSocial($email, $name, $provider, $providerId, $ip, $userAgent, ?string $profilePic = null)
    {
        $em = $this->entityManager;
        $user = null;

        // Try to look up by OAuth Provider ID first
        if ($provider === 'google' && !empty($providerId)) {
            $user = $em->getRepository(User::class)->findOneBy(['googleId' => $providerId]);
        } elseif ($provider === 'apple' && !empty($providerId)) {
            $user = $em->getRepository(User::class)->findOneBy(['appleId' => $providerId]);
        }

        // Fallback to email lookup
        if (!$user && !empty($email)) {
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        }

        if (!$user) {
            $user = new User();
            $user->setUsername($email)
                ->setEmail($email)
                ->setFullname($name ?: strstr($email, '@', true))
                ->setPassword(\Authentication\Service\AuthenticationService::encryptPassword(bin2hex(random_bytes(16))))
                ->setRole($em->find(\Authentication\Entity\Roles::class, \Authentication\Service\AuthenticationService::USER_ROLE_CUSTOMER))
                ->setState($em->find(\Authentication\Entity\UserState::class, \Authentication\Service\AuthenticationService::USER_STATE_ENABLED))
                ->setCreatedOn(new \DateTime())
                ->setRegistrationDate(new \DateTime())
                ->setEmailConfirmed(true)
                ->setIsProfiled(false)
                ->setUid(uniqid("resu"))
                ->setUuid(Uuid::uuid4()->toString());

            if ($provider === 'google' && !empty($providerId)) {
                $user->setGoogleId($providerId);
                if ($profilePic !== null && $profilePic !== '') {
                    $user->setProfilePic($profilePic);
                }
            } elseif ($provider === 'apple' && !empty($providerId)) {
                $user->setAppleId($providerId);
            }

            $em->persist($user);
            $em->flush();
        } else {
            if ($user->getState()->getId() != 1) {
                throw new \Exception("Your account is disabled");
            }

            // Link OAuth ID to existing account if not yet set
            $modified = false;
            if ($provider === 'google' && !empty($providerId) && empty($user->getGoogleId())) {
                $user->setGoogleId($providerId);
                $modified = true;
            } elseif ($provider === 'apple' && !empty($providerId) && empty($user->getAppleId())) {
                $user->setAppleId($providerId);
                $modified = true;
            }

            if ($provider === 'google' && $profilePic !== null && $profilePic !== '' && $user->getProfilePic() !== $profilePic) {
                $user->setProfilePic($profilePic);
                $modified = true;
            }

            if ($modified) {
                $em->persist($user);
                $em->flush();
            }
        }

        $uuid = Uuid::uuid4();
        $refresh_uid = uniqid("rt", true);

        $data_r = [
            "uuid" => $user->getUuid(),
            "uid" => $user->getUid(),
            "aud" => $uuid,
            "email" => $user->getEmail(),
            "role" => $user->getRole()->getId(),
            "token_id" => self::generateTokenId(),
        ];

        $data_r["token"] = $this->jwtIssuer->issueToken($data_r)->toString();
        $data_r["userid"] = $user->getId();
        $data_r["expire"] = 1800; // fix expiry date
        $data_r["u_uid"] = $user->getUid();
        $data_r["refresh_uid"] = $refresh_uid;

        $data = [];
        $data['fullname'] = $user->getFullname();
        $data["email"] = $user->getEmail();
        $data["uuid"] = $user->getUuid();
        $data["username"] = $user->getUsername();
        $data["role"] = $user->getRole()->getName();
        $data["role_id"] = $user->getRole()->getId();
        $data["wallet"] = $user->getWallet() == null ? 0 : $user->getWallet()->getBalance();
        $data["profile_pic"] = $user->getProfilePic();

        // Generate refresh token
        $refreshData = [];
        $refreshData["ip"] = $ip;
        $refreshData["data"] = $data_r;
        $refreshData["user_agent"] = $userAgent;
        $refreshData["refresh_uid"] = $refresh_uid;
        $refreshData["uid"] = $data_r["uid"];
        $refreshData["user_id"] = $user->getId();

        $refreshToken = $this->jwtIssuer->generateRefreshToken($refreshData);
        $cookie = new SetCookie(self::COOKIE_NAME);
        $cookie->setValue($refreshToken);
        $cookie->setExpires(60 * 60 * 24 * 30);
        $cookie->setPath("/");
        $cookie->setSecure(true);
        $cookie->setHttponly(true);
        
        $config = $this->jwtIssuer->getSystemConfig();
        $cookie->setDomain($config["jwt"]["url"]);

        $data["cookie"] = $cookie;

        return array_merge($data, $data_r);
    }
}
