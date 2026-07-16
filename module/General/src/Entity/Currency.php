<?php

namespace General\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Currency
 *
 * @ORM\Table(name="currency")
 * @ORM\Entity
 */
class Currency
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
     * @var string @ORM\Column(name="title", type="string", length=45, nullable=true)
     */
    private $title;
/**
     *
     * @var string @ORM\Column(name="code", type="string", length=10, nullable=true)
     */
    private $code;
/**
     *
     * @var string @ORM\Column(name="iso_code", type="string", length=45, nullable=true)
     */
    private $isoCode;
/**
     * @var string @ORM\Column(name="payment_code", type="string", length=45, nullable=true)
     *
     */
    private $paymentCode;
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
     * Set title
     *
     * @param string $title
     *
     * @return Currency
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return Currency
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
     * Set isoCode
     *
     * @param string $isoCode
     *
     * @return Currency
     */
    public function setIsoCode($isoCode)
    {
        $this->isoCode = $isoCode;
        return $this;
    }

    /**
     * Get isoCode
     *
     * @return string
     */
    public function getIsoCode()
    {
        return $this->isoCode;
    }

    public function setPaymentCode($code)
    {
        $this->paymentCode = $code;
        return $this;
    }

    public function getPaymentCode()
    {
        return $this->paymentCode;
    }
}
