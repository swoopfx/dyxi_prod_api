<?php

namespace General\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Zone
 *
 * @ORM\Table(name="zone", indexes={@ORM\Index(name="FK_zone_country_idx", columns={"country_id"})})
 * @ORM\Entity(repositoryClass="Settings\Entity\Repository\ZoneRepository")
 */
class Zone
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
     * @var string @ORM\Column(name="zone_name", type="string", length=128, nullable=false)
     */
    private $zoneName;
/**
     *
     * @var string @ORM\Column(name="code", type="string", length=32, nullable=false)
     */
    private $code;
/**
     *
     * @var integer @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;
/**
     *
     * @var \Settings\Entity\Country @ORM\ManyToOne(targetEntity="General\Entity\Country")
     *      @ORM\JoinColumns({
     *      @ORM\JoinColumn(name="country_id", referencedColumnName="id")
     *      })
     */
    private $country;
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
     * Set zoneName
     *
     * @param string $zoneName
     *
     * @return Zone
     */
    public function setZoneName($zoneName)
    {
        $this->zoneName = $zoneName;
        return $this;
    }

    /**
     * Get zoneName
     *
     * @return string
     */
    public function getZoneName()
    {
        return $this->zoneName;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return Zone
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Zone
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set country
     *
     * @param \Settings\Entity\Country $country
     *
     * @return Zone
     */
    public function setCountry(\General\Entity\Country $country = null)
    {
        $this->country = $country;
        return $this;
    }

    /**
     * Get country
     *
     * @return \Settings\Entity\Country
     */
    public function getCountry()
    {
        return $this->country;
    }
}
