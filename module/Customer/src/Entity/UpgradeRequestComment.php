<?php

namespace Customer\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="upgrade_request_comment")
 */
class UpgradeRequestComment
{

    /**
     *
     * @var integer @ORM\Column(name="id", type="integer")
     *      @ORM\Id
     *      @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $id;

    /**
     * Undocumented variable
     * @ORM\ManyToOne(targetEntity="UpgradeRequest")
     * @var UpgradeRequest
     */
    private UpgradeRequest  $request;

    /**
     * Undocumented variable
     * @ORM\Column(nullable=false, name="comments")
     * @var string
     */
    private string $comment;


    /**
     *  @ORM\Column(type="datetime", nullable=false)
     */
    private \DateTime $createdOn;

    /**
     * Undocumented variable
     * @ORM\Column(type="datetime", nullable=false)
     * @var \DateTime
     */
    private \DateTime $updatedOn;

    /**
     * Get undocumented variable
     *
     * @return  UpgradeRequest
     */ 
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set undocumented variable
     *
     * @param  UpgradeRequest  $request  Undocumented variable
     *
     * @return  self
     */ 
    public function setRequest(UpgradeRequest $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */ 
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $comment  Undocumented variable
     *
     * @return  self
     */ 
    public function setComment(string $comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get the value of createdOn
     */ 
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    /**
     * Set the value of createdOn
     *
     * @return  self
     */ 
    public function setCreatedOn($createdOn)
    {
        $this->createdOn = $createdOn;
        $this->updatedOn = $createdOn;
        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  \DateTime
     */ 
    public function getUpdatedOn()
    {
        return $this->updatedOn;
    }

    /**
     * Set undocumented variable
     *
     * @param  \DateTime  $updatedOn  Undocumented variable
     *
     * @return  self
     */ 
    public function setUpdatedOn(\DateTime $updatedOn)
    {
        $this->updatedOn = $updatedOn;

        return $this;
    }
}
