<?php

namespace  Authentication\Entity;

use Doctrine\ORM\Mapping as ORM;
use Authentication\Entity\User;

/**
 * User refresh token entity
 *
 * @ORM\Entity
 * @ORM\Table(name="user_refresh_token")
 */
class UserRefreshToken
{
    /**
     *
     * @var integer
     * @ORM\Column(name="id", type="integer")
     *      @ORM\Id
     *      @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * Token Value
     * @ORM\Column(type="text", nullable=false)
     * @var string
     */
    private $refreshToken;

    /**
     * User agent requesting the data
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $userAgent;

    /**
     * Used to identify the specific token refresh token
     * @ORM\Column(nullable=false)
     * @var string
     */
    private $tokenId;

    /**
     * Users IP address
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $userIp;

    /**
     * User associated with the token
     * @ORM\ManyToOne(targetEntity="Authentication\Entity\User")
     * @var User
     */
    private $userId;

    /**
     * Date token created
     * @ORM\Column(type="datetime", nullable=false)
     * @var \Datetime
     */
    private $createdOn;

    /**
     * Undocumented variable
     *
     * @var \Datetime
     */
    private $updatedOn;

    /**
     * Date time Token expires
     * @ORM\Column(type="datetime", nullable=false)
     * @var \Datetime
     */
    private $expiresOn;

    /**
     * UUID identifier  returned with the login response and uniquely identiies the device
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $uuid;

    /**
     * Encrypted format of the rfresh token
     * @ORM\Column(type="string", nullable=false)
     * @var string
     */
    private $refreshUid;






    /**
     * Get
     *
     * @return  integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get token Value
     *
     * @return  string
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * Set token Value
     *
     * @param  string  $refreshToken  Token Value
     *
     * @return  self
     */
    public function setRefreshToken(string $refreshToken)
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    /**
     * Get user agent requesting the data
     *
     * @return  string
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * Set user agent requesting the data
     *
     * @param  string  $userAgent  User agent requesting the data
     *
     * @return  self
     */
    public function setUserAgent(string $userAgent)
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * Get users IP address
     *
     * @return  string
     */
    public function getUserIp()
    {
        return $this->userIp;
    }

    /**
     * Set users IP address
     *
     * @param  string  $userIp  Users IP address
     *
     * @return  self
     */
    public function setUserIp(string $userIp)
    {
        $this->userIp = $userIp;

        return $this;
    }

    /**
     * Get user associated with the token
     *
     * @return  User
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set user associated with the token
     *
     * @param  User  $userId  User associated with the token
     *
     * @return  self
     */
    public function setUserId(User $userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get date token created
     *
     * @return  \Datetime
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    /**
     * Set date token created
     *
     * @param  \Datetime  $createdOn  Date token created
     *
     * @return  self
     */
    public function setCreatedOn(\Datetime $createdOn)
    {
        $this->createdOn = $createdOn;

        return $this;
    }

    /**
     * Get date time Token expires
     *
     * @return  \Datetime
     */
    public function getExpiresOn()
    {
        return $this->expiresOn;
    }

    /**
     * Set date time Token expires
     *
     * @param  \Datetime  $expiresOn  Date time Token expires
     *
     * @return  self
     */
    public function setExpiresOn(\Datetime $expiresOn)
    {
        $this->expiresOn = $expiresOn;

        return $this;
    }

    /**
     * Get encrypted format of the rfresh token
     *
     * @return  string
     */
    public function getRefreshUid()
    {
        return $this->refreshUid;
    }

    /**
     * Set encrypted format of the rfresh token
     *
     * @param  string  $refreshUid  Encrypted format of the rfresh token
     *
     * @return  self
     */
    public function setRefreshUid(string $refreshUid)
    {
        $this->refreshUid = $refreshUid;

        return $this;
    }

    /**
     * Get uUID identifier returned with the login response and uniquely identiies the device
     *
     * @return  string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Set uUID identifier returned with the login response and uniquely identiies the device
     *
     * @param  string  $uuid  UUID identifier returned with the login response and uniquely identiies the device
     *
     * @return  self
     */
    public function setUuid(string $uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * Get refresh Identifier, used in comnbination with the userAgent to Regenerate the accessToken
     *
     * @return  string
     */
    public function getTokenId()
    {
        return $this->tokenId;
    }

    /**
     * Set refresh Identifier, used in comnbination with the userAgent to Regenerate the accessToken
     *
     * @param  string  $tokenId  Refresh Identifier, used in comnbination with the userAgent to Regenerate the accessToken
     *
     * @return  self
     */
    public function setTokenId(string $tokenId)
    {
        $this->tokenId = $tokenId;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  \Datetime
     */
    public function getUpdatedOn()
    {
        return $this->updatedOn;
    }

    /**
     * Set undocumented variable
     *
     * @param  \Datetime  $updatedOn  Undocumented variable
     *
     * @return  self
     */
    public function setUpdatedOn(\Datetime $updatedOn)
    {
        $this->updatedOn = $updatedOn;

        return $this;
    }
}
