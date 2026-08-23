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
        
        if (!file_exists($privateKeyPath) || !file_exists($publicKeyPath)) {
            $keyDir = dirname($privateKeyPath);
            if (!is_dir($keyDir)) {
                mkdir($keyDir, 0755, true);
            }
            $configArgs = [
                "private_key_bits" => 2048,
                "private_key_type" => OPENSSL_KEYTYPE_RSA,
            ];
            $res = openssl_pkey_new($configArgs);
            if ($res !== false) {
                openssl_pkey_export($res, $privateKeyContent);
                $publicKeyDetails = openssl_pkey_get_details($res);
                $publicKeyContent = $publicKeyDetails["key"];
                
                file_put_contents($privateKeyPath, $privateKeyContent);
                chmod($privateKeyPath, 0600);
                file_put_contents($publicKeyPath, $publicKeyContent);
                chmod($publicKeyPath, 0644);
            } else {
                throw new \RuntimeException("Failed to generate RSA key pair: " . openssl_error_string());
            }
        }

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
