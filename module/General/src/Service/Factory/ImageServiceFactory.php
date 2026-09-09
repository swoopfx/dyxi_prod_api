<?php

namespace General\Service\Factory;

use General\Service\ImageService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use General\Service\GeneralService;

class ImageServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $xserv = new ImageService();
        /**
         * @var GeneralService
         */
        $generalService = $container->get("general_service");
        $settings = method_exists($generalService, 'getSettings') ? $generalService->getSettings() : null;

        $awsKey = getenv('AWS_ACCESS_KEY') ?: getenv('AWS_ACCESS_KEY_ID') ?: ($settings && method_exists($settings, 'getAwsAccessKey') ? $settings->getAwsAccessKey() : 'dummy_key');
        $awsSecret = getenv('AWS_SECRET_KEY') ?: getenv('AWS_SECRET_ACCESS_KEY') ?: ($settings && method_exists($settings, 'getAwsSecretKey') ? $settings->getAwsSecretKey() : 'dummy_secret');

        $credentials = new Credentials($awsKey, $awsSecret);

        $s3 = new S3Client([
            'version'     => 'latest',
            'region'      => getenv('AWS_REGION') ?: 'us-east-1',
            'credentials' => $credentials
        ]);

        $xserv->setS3Instance($s3)->setEntityManager($generalService->getEm());
        return $xserv;
    }
}
