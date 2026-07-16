<?php

namespace Authentication\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_state")
 *
 */

class UserState
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
     * Status of the user
     * @ORM\Column(nullable=false)
     * @var string
     */
    private $state;

    /**
     * Get the value of id
     *
     * @return  integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get status of the user
     *
     * @return  string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set status of the user
     *
     * @param  string  $state  Status of the user
     *
     * @return  self
     */
    public function setState(string $state)
    {
        $this->state = $state;

        return $this;
    }
}
