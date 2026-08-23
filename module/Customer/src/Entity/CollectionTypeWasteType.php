<?php

namespace Customer\Entity;

use Doctrine\ORM\Mapping as ORM;
use General\Entity\WasteCollectionType;
use General\Entity\WasteType;

/**
 * Undocumented class
 * @ORM\Entity
 * @ORM\Table(name="collection_type_waste_type")
 */
class CollectionTypeWasteType
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
     * @ORM\ManyToOne(targetEntity="General\Entity\WasteType")
     * @var WasteType
     */
    private $wasteType;

    /**
     * Undocumented variable
     * @ORM\ManyToOne(targetEntity="General\Entity\WasteCollectionType")
     * @var WasteCollectionType
     */
    private $collectionType;

    /**
     * Undocumented variable
     * @ORM\Column(nullable=false)
     * @var string
     */
    private $pricePerUnit;

    /**
     * Get @ORM\Column(name="id", type="integer", nullable=false)
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
     * @return  WasteType
     */
    public function getWasteType()
    {
        return $this->wasteType;
    }

    /**
     * Set undocumented variable
     *
     * @param  WasteType  $wasteType  Undocumented variable
     *
     * @return  self
     */
    public function setWasteType(WasteType $wasteType)
    {
        $this->wasteType = $wasteType;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  WasteCollectionType
     */
    public function getCollectionType()
    {
        return $this->collectionType;
    }

    /**
     * Set undocumented variable
     *
     * @param  WasteCollectionType  $collectionType  Undocumented variable
     *
     * @return  self
     */
    public function setCollectionType(WasteCollectionType $collectionType)
    {
        $this->collectionType = $collectionType;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getPricePerUnit()
    {
        return $this->pricePerUnit;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $pricePerUnit  Undocumented variable
     *
     * @return  self
     */
    public function setPricePerUnit(string $pricePerUnit)
    {
        $this->pricePerUnit = $pricePerUnit;

        return $this;
    }
}
