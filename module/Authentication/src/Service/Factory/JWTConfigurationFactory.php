<?php

namespace Authentication\Service\Factory;

use Authentication\Service\JWTConfig;
use Authentication\Service\JWTConfiguration;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Rsa\Sha256;
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
        if (! isset($config['jwt'])) {
            throw new InvalidArgumentException("JWT configuration is missing in the configuration file");
        }
        $jwtConfigEntity = new JWTConfig($config['jwt']);
        $algo = new Sha256();

        // Load RSA keys for asymmetric encryption
        $baseDir = realpath(__DIR__ . '/../../../../../');
        $privateKeyPath = $baseDir . '/data/keys/private.pem';
        $publicKeyPath = $baseDir . '/data/keys/public.pem';
        
        $privateKey = InMemory::file('file://' . $privateKeyPath);
        $publicKey = InMemory::file('file://' . $publicKeyPath);

        $configuration = Configuration::forAsymmetricSigner($algo, $privateKey, $publicKey);
        $configuration->setValidationConstraints(
            new IssuedBy($jwtConfigEntity->getIssuer())
        );

        $refreshConfig = Configuration::forAsymmetricSigner($algo, $privateKey, $publicKey);

        $xserv = new JWTConfiguration();
        $xserv->setConfiguration($configuration)->setJwtConfigEntity($jwtConfigEntity)->setRefreshConfig($refreshConfig);
        return $xserv;
    }
}
