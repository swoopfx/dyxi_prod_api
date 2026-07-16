<?php

namespace Authentication\Options;

use Laminas\Stdlib\AbstractOptions;

class ModuleOptions extends AbstractOptions
{
    /**
     * Turn off strict options mode
     */
    protected $__strictMode__ = false;

    /**
     *
     * @var string
     */
    protected $loginRedirectRoute = 'dashboard';

    /**
     *
     * @var string
     */
    protected $logoutRedirectRoute = 'user-index';

    /**
     *
     * @var string
     */
    protected $senderEmailAdress = 'no-reply@example.com';


    private $entityManager;



    // public function getUser


    /**
     * Get the value of entityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * Set the value of entityManager
     *
     * @return  self
     */
    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;

        return $this;
    }
}
