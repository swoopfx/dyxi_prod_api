<?php

namespace Authentication\Entity;

use Doctrine\Common\Collections\ArrayCollection as ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="roles")
 */

class Roles
{
    /**
     *
     * @var integer @ORM\Column(name="id", type="integer")
     *      @ORM\Id
     *      @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(nullable=false)
     */
    protected $name;

    /**
     * This is the default page name to user redirect to
     * If a referer login does not exist e.g /broker or /brokerchild
     * @ORM\Column(nullable=true)
     */
    private $dropPage;

    /**
     * @var Array
     *
     * @ORM\ManyToMany(targetEntity="Roles", cascade={"persist"})
     * @ORM\JoinTable(name="roles_parents",
     *      joinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="parent_id", referencedColumnName="id")}
     *      )
     */
    protected $parents;

    // protected $permissions;

    // /**
    //  * Undocumented variable
    //  * @ORM\Column(type="datetime")
    //  * @var \Datetime
    //  */
    // private $updatedOn;


    public function __construct()
    {
        $this->parents = new ArrayCollection();
    }


    public function getId()
    {
        return $this->id;
    }

    /**
     * Get this is the default page name to user redirect to
     */
    public function getDropPage()
    {
        return $this->dropPage;
    }

    /**
     * Set this is the default page name to user redirect to
     *
     * @return  self
     */
    public function setDropPage($dropPage)
    {
        $this->dropPage = $dropPage;

        return $this;
    }

    /**
     * Get the value of name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @return  self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of parent
     *
     * @return  Array
     */
    public function getParents()
    {
        return $this->parents;
    }

    /**
     * Set the value of parent
     *
     * @param  Array  $parent
     *
     * @return  self
     */
    public function setParents(array $parent)
    {
        $this->parents = $parent;

        return $this;
    }
}
