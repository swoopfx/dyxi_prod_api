<?php

namespace Authentication\Service\Factory;

use Authentication\Entity\Jwt;
use Authentication\Service\JWTConfiguration;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;

class JWTConfigurationFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        if (! $container->has("config")) {
            throw new InvalidArgumentException("JWT configuration factory cannot retrieve configuration data");
        }

        $config = $container->get("config");
        $algo = new Sha256();
        $generalService = $container->get("general_service");
        $em = $generalService->getEm();
      /**
       * @var Jwt
       */
        $jwtConfigEntity = $em->find(Jwt::class, 100);
      // var_dump($jwtConfigEntity->getSignKey());
        $key = InMemory::base64Encoded($jwtConfigEntity->getSignKey());
      // $refeshKey = InMemory::base64Encoded($config["jwt"]["refreshKey"]);
        $configuration = Configuration::forSymmetricSigner($algo, $key);
        $configuration->setValidationConstraints(
            new IssuedBy($jwtConfigEntity->getIssuer())
        );

        $refreshKey = InMemory::base64Encoded($jwtConfigEntity->getRefreshKey());
        $refreshConfig = Configuration::forSymmetricSigner($algo, $refreshKey);
      // $refreshConfig->setValidationConstraints()

        $xserv = new JWTConfiguration();
        $xserv->setConfiguration($configuration)->setJwtConfigEntity($jwtConfigEntity)->setRefreshConfig($refreshConfig);
        return $xserv;
    }
}
