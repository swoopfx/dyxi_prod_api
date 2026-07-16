<?php

namespace Authentication\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entity for Jwt
 *  @ORM\Entity
 *  @ORM\Table(name="jwt")
 */
class Jwt
{
    /**
     *
     * @var integer @ORM\Column(name="id", type="integer")
     *      @ORM\Id
     *      @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * Undocumented variable
     * @ORM\Column(type="text")
     * @var string
     */
    private $signKey;

    /**
     * Undocumented variable
     * @ORM\Column(type="text")
     * @var string
     */
    private $refreshKey;

    /**
     * Undocumented variable
     * @ORM\Column(type="text")
     * @var string
     */
    private $verifyKkey;
    /**
     * Undocumented variable
     * @ORM\Column(type="string")
     * @var string
     */
    private $issuer;

    /**
     * Undocumented variable
     * @ORM\Column(type="string", length="100")
     * @var string
     */
    private $aud;

    /**
     * Undocumented variable
     * @ORM\Column(nullable=false, length="100")
     * @var string
     */
    private $secretKeyExpires;

    /**
     *
     * @ORM\Column(nullable=false, length="100")
     * @var string
     */
    private $refreshKeyExpires;

    /**
     * Undocumented variable
     * @ORM\Column(type="datetime")
     * @var Datetime
     */
    private $createdOn;

    /**
     * Undocumented variable
     * @ORM\Column(type="datetime")
     * @var Datetime
     */
    private $updatedOn;

    /**
     * Get @ORM\Column(name="id", type="integer")
     *
     * @return  integer
     */
    public function getId()
    {
        return $this->id;
    }



    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getIssuer()
    {
        return $this->issuer;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $issuer  Undocumented variable
     *
     * @return  self
     */
    public function setIssuer(string $issuer)
    {
        $this->issuer = $issuer;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getAud()
    {
        return $this->aud;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $aud  Undocumented variable
     *
     * @return  self
     */
    public function setAud(string $aud)
    {
        $this->aud = $aud;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  Datetime
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    /**
     * Set undocumented variable
     *
     * @param  Datetime  $createdOn  Undocumented variable
     *
     * @return  self
     */
    public function setCreatedOn(\Datetime $createdOn)
    {
        $this->createdOn = $createdOn;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  Datetime
     */
    public function getUpdatedOn()
    {
        return $this->updatedOn;
    }

    /**
     * Set undocumented variable
     *
     * @param  Datetime  $updatedOn  Undocumented variable
     *
     * @return  self
     */
    public function setUpdatedOn(\Datetime $updatedOn)
    {
        $this->updatedOn = $updatedOn;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getSignKey()
    {
        return $this->signKey;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $signKey  Undocumented variable
     *
     * @return  self
     */
    public function setSignKey(string $signKey)
    {
        $this->signKey = $signKey;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getRefreshKey()
    {
        return $this->refreshKey;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $refreshKey  Undocumented variable
     *
     * @return  self
     */
    public function setRefreshKey(string $refreshKey)
    {
        $this->refreshKey = $refreshKey;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getVerifyKkey()
    {
        return $this->verifyKkey;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $verifyKkey  Undocumented variable
     *
     * @return  self
     */
    public function setVerifyKkey(string $verifyKkey)
    {
        $this->verifyKkey = $verifyKkey;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getSecretKeyExpires()
    {
        return $this->secretKeyExpires;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $secretKeyExpires  Undocumented variable
     *
     * @return  self
     */
    public function setSecretKeyExpires(string $secretKeyExpires)
    {
        $this->secretKeyExpires = $secretKeyExpires;

        return $this;
    }

    /**
     * Get the value of refreshKeyExpires
     *
     * @return  string
     */
    public function getRefreshKeyExpires()
    {
        return $this->refreshKeyExpires;
    }

    /**
     * Set the value of refreshKeyExpires
     *
     * @param  string  $refreshKeyExpires
     *
     * @return  self
     */
    public function setRefreshKeyExpires(string $refreshKeyExpires)
    {
        $this->refreshKeyExpires = $refreshKeyExpires;

        return $this;
    }
}
