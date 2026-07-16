<?php

namespace General\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Country
 *
 * @ORM\Table(name="country")
 * @ORM\Entity
 */
class Country
{
    /**
     *
     * @var integer @ORM\Column(name="id", type="integer", nullable=false)
     *      @ORM\Id
     *      @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
/**
     *
     * @var string
     * @ORM\Column(name="country_name", type="string", length=128, nullable=true)
     */
    private $countryName;
/**
     *
     * @var string
     * @ORM\Column(name="iso_code_2", type="string", length=2, nullable=true)
     */
    private $isoCode2;
/**
     *
     * @var string
     * @ORM\Column(name="iso_code_3", type="string", length=6, nullable=true)
     */
    private $isoCode3;
/**
     *
     * @var string
     * @ORM\Column(name="address_format", type="text", nullable=true)
     */
    private $addressFormat;
/**
     *
     * @var boolean
     * @ORM\Column(name="postcode_required", type="boolean", nullable=true)
     */
    private $postcodeRequired;
/**
     *
     * @var boolean
     * @ORM\Column(name="status", type="boolean", nullable=true)
     */
    private $status;
/**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set countryName
     *
     * @param string $countryName
     * @return Country
     */
    public function setCountryName($countryName)
    {
        $this->countryName = $countryName;
        return $this;
    }

    /**
     * Get countryName
     *
     * @return string
     */
    public function getCountryName()
    {
        return $this->countryName;
    }

    /**
     * Set isoCode2
     *
     * @param string $isoCode2
     * @return Country
     */
    public function setIsoCode2($isoCode2)
    {
        $this->isoCode2 = $isoCode2;
        return $this;
    }

    /**
     * Get isoCode2
     *
     * @return string
     */
    public function getIsoCode2()
    {
        return $this->isoCode2;
    }

    /**
     * Set isoCode3
     *
     * @param string $isoCode3
     * @return Country
     */
    public function setIsoCode3($isoCode3)
    {
        $this->isoCode3 = $isoCode3;
        return $this;
    }

    /**
     * Get isoCode3
     *
     * @return string
     */
    public function getIsoCode3()
    {
        return $this->isoCode3;
    }

    /**
     * Set addressFormat
     *
     * @param string $addressFormat
     * @return Country
     */
    public function setAddressFormat($addressFormat)
    {
        $this->addressFormat = $addressFormat;
        return $this;
    }

    /**
     * Get addressFormat
     *
     * @return string
     */
    public function getAddressFormat()
    {
        return $this->addressFormat;
    }

    /**
     * Set postcodeRequired
     *
     * @param boolean $postcodeRequired
     * @return Country
     */
    public function setPostcodeRequired($postcodeRequired)
    {
        $this->postcodeRequired = $postcodeRequired;
        return $this;
    }

    /**
     * Get postcodeRequired
     *
     * @return boolean
     */
    public function getPostcodeRequired()
    {
        return $this->postcodeRequired;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return Country
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Get status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }
}
