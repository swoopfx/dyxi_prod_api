<?php

namespace Authentication\Service;

use Authentication\Entity\User;
use Authentication\Entity\UserRefreshToken;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\IdentifiedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\ValidAt;
use Doctrine\ORM\EntityManager;
use Exception;
use Laminas\Db\Sql\Ddl\Column\Datetime;
use Laminas\Mvc\Plugin\Identity\Identity;
use Authentication\Entity\Jwt;
use Authentication\Exceptions\EmptyTokenException;
use Authentication\Exceptions\InvalidTokenException;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Lcobucci\JWT\Validation\Validator;
use Authentication\Service\JWTConfiguration;
use Lcobucci\Clock\FrozenClock;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;

class JWTIssuer
{
    /**
     * @var JWTConfiguration
     */
    private $config;

    // /**
    //  * Undocumented variable
    //  *
    //  * @var Jw
    //  */
    // private $jwtConfigEntity;

    /**
     * @var array
     */
    private $systemConfig;

    /**
     * ORM EntityManager
     *
     * @var EntityManager
     */
    private $entityManager;


    public function __construct()
    {
    }

    public function issueToken($data)
    {
        $now   = new \DateTimeImmutable();
        /**
         * @var Configuration
         */
        $config = $this->config->getConfiguration();
        $jwtConfigEntity = $this->config->getJwtConfigEntity();
        return  $config->builder()
            ->issuedBy($jwtConfigEntity->getIssuer())
            ->permittedFor($jwtConfigEntity->getIssuer())
            ->identifiedBy($data["email"]) // device ID
            ->relatedTo($data["email"])->withClaim("coded", $data)
            ->issuedAt($now)
            ->expiresAt($now->modify($jwtConfigEntity->getSecretKeyExpires()))
            ->getToken($config->signer(), $config->signingKey());
    }


    /**
     * returns data of the token
     *
     * @param string $jwt
     * @return mixed
     */
    public function retreiveTokenData($jwt)
    {
        try {
            $config = $this->config->getConfiguration();

            if (! isset($jwt)) {
                throw new EmptyTokenException("No token provided");
            }

            /**
             * @var Token
             */
            $token = $config->parser()->parse($jwt);
            assert($token instanceof UnencryptedToken);
            return [
                "coded" => $token->claims()->get("coded"),
                "expDate" => $token->claims()->get("exp"),
                "issDate" => $token->claims()->get("iat")
            ];
        } catch (\Throwable $th) {
            throw new InvalidTokenException($th->getMessage());
        }
    }


    public function generateRefreshToken($data)
    {
        try {
            // generate refresh token
            $now   = new \DateTimeImmutable();
            /**
             * @var Configuration
             */
            $config = $this->config->getRefreshConfig();
            $jwtConfigEntity = $this->config->getJwtConfigEntity();
            $expiresOn = $now->modify($jwtConfigEntity->getRefreshKeyExpires());
            $refreshToken = $config->builder()

                // ->issuedBy($this->jwtConfig->getIssuer())
                // ->permittedFor($this->jwtConfig->getIssuer())
                // ->identifiedBy($data["email"]) // device ID
                // ->relatedTo($data["email"])->withClaim("coded", $data)
                // ->issuedAt($now)
                // ->expiresAt($now->modify($this->jwtConfig->getSecretKeyExpires()))
                // ->getToken($this->config->signer(), $this->config->signingKey());

                ->issuedBy($jwtConfigEntity->getIssuer())
                ->permittedFor($jwtConfigEntity->getIssuer())
                ->identifiedBy($data["data"]["email"])
                ->relatedTo($data["data"]["email"])->withClaim("coded", $data["data"])
                ->issuedAt($now)
                ->expiresAt($expiresOn)
                ->getToken($config->signer(), $config->signingKey());

            // Hydrate  into data base

            $datetime = new \Datetime();
            $userRefreshTokenEntity = new UserRefreshToken();
            $userRefreshTokenEntity->setCreatedOn(new \DateTime())
                // ->setUserAgent($data["user_agent"])
                ->setTokenId($data["data"]["token_id"])
                ->setExpiresOn(\Datetime::createFromImmutable($expiresOn))
                ->setUserId($this->entityManager->find(User::class, $data["user_id"]))
                ->setUuid($data["data"]["aud"])
                ->setRefreshUid(AuthenticationService::encryptPassword($data["refresh_uid"]))
                ->setRefreshToken($refreshToken->toString());
                // ->setUserIp($data["ip"]);

            $this->entityManager->persist($userRefreshTokenEntity);
            $this->entityManager->flush();

            return $refreshToken->toString();
        } catch (\Throwable $th) {
            //throw $th;
            throw new \Exception($th->getMessage());
        }
    }

    public function validateRefreshToken($jwt)
    {
        try {
            $config = $this->config->getRefreshConfig();
            $jwtConfigEntity = $this->config->getJwtConfigEntity();

            if (! isset($jwt)) {
                throw new \Exception("No token provided");
                // exit();
            }

            /**
             * @var Token
             */
            $token = $config->parser()->parse($jwt);

            assert($token instanceof UnencryptedToken);

            $validation = new Validator();
            $validation->assert($token, new IssuedBy($jwtConfigEntity->getIssuer()));
            $validation->assert($token, new PermittedFor($jwtConfigEntity->getIssuer()));
            $validation->assert($token, new LooseValidAt(new FrozenClock(new \DateTimeImmutable())));
            $validation->assert($token, new IdentifiedBy($token->claims()->get("jti")));
            $validation->assert($token, new SignedWith($config->signer(), $config->signingKey()));
            // if ($token instanceof UnencryptedToken) {
            //     $constraints = $config->validationConstraints();
            //     if ($config->validator()->validate($token, ...$constraints)) {
            //         return $token;
            //     } else {
            //         return null;
            //     }
            // }
            return $token;
        } catch (RequiredConstraintsViolated $e) {
            throw new InvalidTokenException(json_encode($e->violations()));
            // return $
        }
    }

    /**
     * This function validated the access token
     *
     * @param string $jwt
     * @return string
     */
    public function validateToken($jwt)
    {
        try {
            $config = $this->config->getConfiguration();
            $jwtConfigEntity = $this->config->getJwtConfigEntity();

            if (! isset($jwt)) {
                throw new \Exception("No token provided");
                // exit();
            }

            /**
             * @var Token
             */
            $token = $config->parser()->parse($jwt);

            assert($token instanceof UnencryptedToken);

            $validation = new Validator();
            $validation->assert($token, new IssuedBy($jwtConfigEntity->getIssuer()));
            $validation->assert($token, new PermittedFor($jwtConfigEntity->getIssuer()));
            $validation->assert($token, new LooseValidAt(new FrozenClock(new \DateTimeImmutable())));
            $validation->assert($token, new IdentifiedBy($token->claims()->get("jti")));
            $validation->assert($token, new SignedWith($config->signer(), $config->signingKey()));
            // if ($token instanceof UnencryptedToken) {
            //     $constraints = $config->validationConstraints();
            //     if ($config->validator()->validate($token, ...$constraints)) {
            //         return $token;
            //     } else {
            //         return null;
            //     }
            // }
            return $token;
        } catch (RequiredConstraintsViolated $e) {
            throw new InvalidTokenException(json_encode($e->violations()));
            // return $
        }
    }

    /**
     * This function validate the expired acccess token over all contrainst except iat, nbf and exp
     *
     * @param string $jwt
     * @return string
     */
    public function expiredValidateToken($jwt)
    {
        try {
            $config = $this->config->getConfiguration();
            $jwtConfigEntity = $this->config->getJwtConfigEntity();

            if (! isset($jwt)) {
                throw new \Exception("No token provided");
                // exit();
            }

            /**
             * @var Token
             */
            $token = $config->parser()->parse($jwt);

            assert($token instanceof UnencryptedToken);

            $validation = new Validator();
            $validation->assert($token, new IssuedBy($jwtConfigEntity->getIssuer()));
            $validation->assert($token, new PermittedFor($jwtConfigEntity->getIssuer()));
            // $validation->assert($token, new LooseValidAt(new FrozenClock(new \DateTimeImmutable())));
            $validation->assert($token, new IdentifiedBy($token->claims()->get("jti")));
            $validation->assert($token, new SignedWith($config->signer(), $config->signingKey()));

            return $token;
        } catch (RequiredConstraintsViolated $e) {
            throw new InvalidTokenException(json_encode($e->violations()));
            // return $
        }
    }


    /**
     * Get the value of config
     *
     * @return  Configuration
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Set the value of config
     *
     * @param   $config
     *
     * @return  self
     */
    public function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Get the value of systemConfig
     *
     * @return  array
     */
    public function getSystemConfig()
    {
        return $this->systemConfig;
    }

    /**
     * Set the value of systemConfig
     *
     * @param  array  $systemConfig
     *
     * @return  self
     */
    public function setSystemConfig(array $systemConfig)
    {
        $this->systemConfig = $systemConfig;

        return $this;
    }

    /**
     * Get oRM EntityManager
     *
     * @return  EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * Set oRM EntityManager
     *
     * @param  EntityManager  $entityManager  ORM EntityManager
     *
     * @return  self
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;

        return $this;
    }
}
