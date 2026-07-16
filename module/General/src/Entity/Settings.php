<?php

namespace General\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="settings")
 */

class Settings
{
    /**
     *
     * @var integer @ORM\Column(name="id", type="integer", nullable=false)
     *      @ORM\Id
     *      @ORM\GeneratedValue(strategy="IDENTITY")
     */

    private $id;

    /**
     * Undocumented variable
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $pusherAppKey;

    /**
     * Undocumented variable
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $pusherSecretKey;

    /**
     * Undocumented variable
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $pusherChannel;

    /**
     * Undocumented variable
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $pusherAppId;

    /**
     * Undocumented variable
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $pusherAppCluster;

    /**
     * Undocumented variable
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $googleAPiKey;


    /**
     * Undocumented variable
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $mailtrapToken;


    /**
     * @ORM\Column(nullable=true)
     *
     * @var string
     */
    private $awsAccessKey;

    /**
     * @ORM\Column(nullable=true)
     *
     * @var string
     */
    private $awsSecretKey;

    /**
     * Get the value of id
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
    public function getPusherAppKey()
    {
        return $this->pusherAppKey;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $pusherAppKey  Undocumented variable
     *
     * @return  self
     */
    public function setPusherAppKey(string $pusherAppKey)
    {
        $this->pusherAppKey = $pusherAppKey;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getPusherSecretKey()
    {
        return $this->pusherSecretKey;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $pusherSecretKey  Undocumented variable
     *
     * @return  self
     */
    public function setPusherSecretKey(string $pusherSecretKey)
    {
        $this->pusherSecretKey = $pusherSecretKey;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getPusherAppId()
    {
        return $this->pusherAppId;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $pusherAppId  Undocumented variable
     *
     * @return  self
     */
    public function setPusherAppId(string $pusherAppId)
    {
        $this->pusherAppId = $pusherAppId;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getGoogleAPiKey()
    {
        return $this->googleAPiKey;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $googleAPiKey  Undocumented variable
     *
     * @return  self
     */
    public function setGoogleAPiKey(string $googleAPiKey)
    {
        $this->googleAPiKey = $googleAPiKey;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getPusherAppCluster()
    {
        return $this->pusherAppCluster;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $pusherAppCluster  Undocumented variable
     *
     * @return  self
     */
    public function setPusherAppCluster(string $pusherAppCluster)
    {
        $this->pusherAppCluster = $pusherAppCluster;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getPusherChannel()
    {
        return $this->pusherChannel;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $pusherChannel  Undocumented variable
     *
     * @return  self
     */
    public function setPusherChannel(string $pusherChannel)
    {
        $this->pusherChannel = $pusherChannel;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getMailtrapToken()
    {
        return $this->mailtrapToken;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $mailtrapToken  Undocumented variable
     *
     * @return  self
     */
    public function setMailtrapToken(string $mailtrapToken)
    {
        $this->mailtrapToken = $mailtrapToken;

        return $this;
    }

    /**
     * Get the value of awsAccessKey
     *
     * @return  string
     */
    public function getAwsAccessKey()
    {
        return $this->awsAccessKey;
    }

    /**
     * Set the value of awsAccessKey
     *
     * @param  string  $awsAccessKey
     *
     * @return  self
     */
    public function setAwsAccessKey(string $awsAccessKey)
    {
        $this->awsAccessKey = $awsAccessKey;

        return $this;
    }

    /**
     * Get the value of awsSecretKey
     *
     * @return  string
     */
    public function getAwsSecretKey()
    {
        return $this->awsSecretKey;
    }

    /**
     * Set the value of awsSecretKey
     *
     * @param  string  $awsSecretKey
     *
     * @return  self
     */
    public function setAwsSecretKey(string $awsSecretKey)
    {
        $this->awsSecretKey = $awsSecretKey;

        return $this;
    }
}
