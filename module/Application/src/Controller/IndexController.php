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
        ini_set('display_errors', '0');
        $openapi = (new \OpenApi\Generator())->generate([
            __DIR__ . '/../../../Application/src/Controller',
            __DIR__ . '/../../../Authentication/src/Controller',
            __DIR__ . '/../../../Resources/src/Controller',
            __DIR__ . '/../../../Evaluation/src/Controller',
            __DIR__ . '/../../../General/src/Controller'
        ]);

        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        $currentServerUrl = $scheme . '://' . $host;

        $servers = [
            new \OpenApi\Annotations\Server([
                'url' => $currentServerUrl,
                'description' => 'Current Active Server (' . $host . ')'
            ]),
            new \OpenApi\Annotations\Server([
                'url' => '/',
                'description' => 'Relative Path'
            ])
        ];
        $openapi->servers = $servers;

        $response = $this->getResponse();
        $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        $response->setContent($openapi->toJson());
        return $response;
    }
}
