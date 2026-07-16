<?php

namespace General\Service;

use Doctrine\ORM\EntityManager;
use AWs\S3\S3Client;
use General\Entity\Image;

class ImageService
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
     * @var S3Client
     */
    private $s3Instance;


    const MAX_FILE_SIZE = 4582853;

    const AWS_BUCKET_NAME = "aibnigeria";



    // Image
    const JPEG_MIME_TYPE = "image/jpeg";

    const PNG_MIME_TYPE = "image/png";

    const BMP_MIME_TYPE = "image/bmp";

    const GIF_MIME_TYPE = "image/gif";


    protected $error_messages = array(
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE (10MB) specified',
        3 => 'The uploaded file was only partially uploaded',
        4 => 'No file was uploaded',
        6 => 'Missing a temporary folder',
        7 => 'Failed to write file to disk'
    );


    private function cleanBlobName($name)
    {
        $name = str_replace(" ", "-", $name);
        $name = time() . "_" . $name;
        return $name;
    }

    public function upload($file)
    {

        $uploadEntity = new Image();
        $blobName = $this->cleanBlobName($file["name"]);
        $entityManager = $this->entityManager;
        if ($file["size"] > self::MAX_FILE_SIZE) {
            // Trigger an event
            throw new \Exception($this->error_messages[2]);
        } elseif ($file['tmp_name'] == NULL) {
            throw new \Exception($this->error_messages[4]);
        } else {

            // $content = fopen($file['tmp_name'], 'r');

            try {

                $this->s3Instance->putObject([
                    'Bucket' => self::AWS_BUCKET_NAME,
                    'Key'    => $blobName,
                    'SourceFile' => $file['tmp_name'],
                    'ACL'        => 'public-read'
                ]);


                $loadUri = $this->s3Instance->getEndpoint();
                $docUrl = $loadUri . "/" . self::AWS_BUCKET_NAME . '/' . $blobName;

                $mimeType = $file["type"];
                $uploadEntity->setCreatedOn(new \Datetime())->setImageUid(self::docCode())
                    ->setImageName($blobName)
                    ->setImageUrl($docUrl)
                    ->setIsHidden(FALSE)
                    ->setMimeType($mimeType)
                    ->setDocExt($mimeType);

                $entityManager->persist($uploadEntity);
                $entityManager->flush();

                return $uploadEntity;
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
    }

    private static function docCode()
    {
        return uniqid("doc");
    }



    public function getUpload()
    {
    }

    //  private function 

    /**
     * Get the value of s3Instance
     */
    public function getS3Instance()
    {
        return $this->s3Instance;
    }

    /**
     * Set the value of s3Instance
     *
     * @return  self
     */
    public function setS3Instance($s3Instance)
    {
        $this->s3Instance = $s3Instance;

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
}
