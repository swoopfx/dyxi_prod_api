<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        return new ViewModel();
    }

    public function swaggerAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setTerminal(true);
        return $viewModel;
    }

    public function swaggerJsonAction()
    {
        $openapi = (new \OpenApi\Generator())->generate([
            __DIR__ . '/../../../Application/src/Controller',
            __DIR__ . '/../../../Authentication/src/Controller',
            __DIR__ . '/../../../Resources/src/Controller',
            __DIR__ . '/../../../Evaluation/src/Controller',
            __DIR__ . '/../../../General/src/Controller'
        ]);
        $response = $this->getResponse();
        $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        $response->setContent($openapi->toJson());
        return $response;
    }
}
