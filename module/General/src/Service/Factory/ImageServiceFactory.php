<?php

namespace General\Service\Factory;

use General\Service\ImageService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use General\Entity\Settings;
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
        /**
         * @var Settings
         */
        $settings = $generalService->getSettings();
        $credentials = new Credentials($settings->getAwsAccessKey(), $settings->getAwsSecretKey());

        $s3 = new S3Client([
            'version'     => 'latest',
            'region'      => 'us-east-1',
            'credentials' => $credentials
        ]);

        $xserv->setS3Instance($s3)->setEntityManager($generalService->getEm());
        return $xserv;
    }
}
