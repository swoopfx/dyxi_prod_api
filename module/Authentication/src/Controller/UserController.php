<?php

namespace Authentication\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

class UserController extends AbstractActionController
{
    private $entityManager;

    private $generalService;

    // private

    public function indexAction()
    {
        $jsonModel = new JsonModel([]);
        return $jsonModel;
    }


    public function profileAction()
    {
        // Get user Details
        // Get Wallet Balnace;
        // Get parent
        $jsonModel = new JsonModel();
        return $jsonModel;
    }
}
