<?php

namespace General\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="image")
 */

class Image
{
    /**
     *
     * @var integer @ORM\Column(name="id", type="integer", nullable=false)
     *      @ORM\Id
     *      @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @ORM\Column(name="image_uid", type="string", nullable=true)
     *
     * @var string
     */
    private $imageUid;

    /**
     * @ORM\Column(name="image_url", type="string", nullable=true)
     *
     * @var string
     */
    private $imageUrl;

    /**
     * This is a lower resolution of Image uploaded
     *
     * @ORM\Column(name="low_resolution", type="string", nullable=true)
     *
     * @var string
     */
    private $lowres;

    /**
     * @ORM\Column(name="thumbnail", type="string", nullable=true)
     *
     * @var string
     */
    private $thumbnail;

    /**
     *
     * @var string @ORM\Column(name="image_name", type="string", length=200, nullable=true)
     */
    private $imageName;

    /**
     *
     * @var boolean @ORM\Column(name="is_hidden", type="boolean", nullable=true)
     */
    private $isHidden;

    /**
     *
     * @var string @ORM\Column(name="mime_type", type="string", length=100, nullable=true)
     */
    private $mimeType;

    /**
     *
     * @var string @ORM\Column(name="image_ext", type="string", length=45, nullable=true)
     */
    private $docExt;

    /**
     * @ORM\Column(name="created_on", type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    private $createdOn;

    /**
     * @ORM\Column(name="updated_on", type="datetime", nullable=true)
     */
    private $updatedOn;

    // /**
    //  * Undocumented variable
    //  * @
    //  * @var string
    //  */
    // private $uuid;

    /**
     */
    public function __construct()
    {

        // TODO - Insert your code here
    }

    /**
     *
     * @return the $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     *
     * @return the $imageUrl
     */
    public function getImageUrl()
    {
        return $this->imageUrl;
    }

    /**
     *
     * @return the $createdOn
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    /**
     *
     * @return the $updatedOn
     */
    public function getUpdatedOn()
    {
        return $this->updatedOn;
    }

    /**
     *
     * @param number $id
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     *
     * @param string $imageUrl
     */
    public function setImageUrl($imageUrl)
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    /**
     *
     * @param DateTime $createdOn
     */
    public function setCreatedOn($createdOn)
    {
        $this->createdOn = $createdOn;
        $this->updatedOn = $createdOn;
        return $this;
    }

    /**
     *
     * @param field_type $updatedOn
     */
    public function setUpdatedOn($updatedOn)
    {
        $this->updatedOn = $updatedOn;
        return $this;
    }

    /**
     *
     * @return the $imageName
     */
    public function getImageName()
    {
        return $this->imageName;
    }

    /**
     *
     * @return the $isHidden
     */
    public function getIsHidden()
    {
        return $this->isHidden;
    }

    /**
     *
     * @return the $mimeType
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     *
     * @return the $docExt
     */
    public function getDocExt()
    {
        return $this->docExt;
    }

    /**
     *
     * @param string $imageName
     */
    public function setImageName($imageName)
    {
        $this->imageName = $imageName;
        return $this;
    }

    /**
     *
     * @param boolean $isHidden
     */
    public function setIsHidden($isHidden)
    {
        $this->isHidden = $isHidden;
        return $this;
    }

    /**
     *
     * @param string $mimeType
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    /**
     *
     * @param string $docExt
     */
    public function setDocExt($docExt)
    {
        $this->docExt = $docExt;
        return $this;
    }

    /**
     *
     * @return the $imageUid
     */
    public function getImageUid()
    {
        return $this->imageUid;
    }

    /**
     *
     * @param string $imageUid
     */
    public function setImageUid($imageUid)
    {
        $this->imageUid = $imageUid;
        return $this;
    }
    /**
     * @return the $thumbnail
     */
    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * @param string $thumbnail
     */
    public function setThumbnail($thumbnail)
    {
        $this->thumbnail = $thumbnail;
        return $this;
    }
    /**
     * @return the $lowres
     */
    public function getLowres()
    {
        return $this->lowres;
    }

    /**
     * @param string $lowres
     */
    public function setLowres($lowres)
    {
        $this->lowres = $lowres;
        return $this;
    }
}
