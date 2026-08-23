<?php

namespace Customer\Entity;

use Doctrine\ORM\Mapping as ORM;
use Authentication\Entity\Roles;

/**
 * Provide information to the
 *  @ORM\Entity
 * @ORM\Table(name="notifications_rpm")
 */
class Notification
{
    /**
     *
     * @var integer @ORM\Column(name="id", type="integer")
     *      @ORM\Id
     *      @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * This is the topic or type of this messages
     * @ORM\Column(type="string")
     * @var string
     */
    private $type;

    /**
     * This is the topic or type of this messages
     * @ORM\Column(type="text")
     * @var string
     */
    private $messages;

    /**
     * This is the topic or type of this messages
     * @ORM\ManyToOne(targetEntity="Authentication\Entity\Roles")
     * @var \Datetime
     */
    private $senderRole;

    /**
     * Undocumented variable
     *
     * @var
     */
    private $sender;

    // private

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
     * Get this is the topic or type of this messages
     *
     * @return  string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set this is the topic or type of this messages
     *
     * @param  string  $type  This is the topic or type of this messages
     *
     * @return  self
     */
    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get this is the topic or type of this messages
     *
     * @return  string
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Set this is the topic or type of this messages
     *
     * @param  string  $messages  This is the topic or type of this messages
     *
     * @return  self
     */
    public function setMessages(string $messages)
    {
        $this->messages = $messages;

        return $this;
    }
}
