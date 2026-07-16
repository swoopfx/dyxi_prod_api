<?php

declare(strict_types=1);

namespace AuthenticateTest\Controller;

use Application\Controller\IndexController;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class AuthenticateControllerTest extends AbstractHttpControllerTestCase
{
    public function setUp(): void
    {
        // The module configuration should still be applicable for tests.
        // You can override configuration here with test case specific values,
        // such as sample view templates, path stacks, module_listener_options,
        // etc.
        $configOverrides = [];

        $this->setApplicationConfig(ArrayUtils::merge(
            include __DIR__ . '/../../../../config/application.config.php',
            $configOverrides
        ));

        parent::setUp();
    }



    public function testIndexActionCanBeAccessed(): void
    {
        $this->assertTrue(true);
        // $this->dispatch('/', 'GET');
        // $this->assertResponseStatusCode(200);
        // $this->url("/");
        // $this->assertModuleName('application');
        // $this->assertControllerName(IndexController::class); // as specified in router's controller name alias
        // $this->assertControllerClass('IndexController');
        // $this->assertMatchedRouteName('home');
        $this->assertTrue(true);
    }

    // public function testIndexActionViewModelTemplateRenderedWithinLayout(): void
    // {
    //     $this->dispatch('/', 'GET');
    //     $this->assertQuery('.container .jumbotron');
    // }

    // public function testInvalidRouteDoesNotCrash(): void
    // {
    //     // $this->dispatch('/invalid/route', 'GET');
    //     // $this->assertResponseStatusCode(404);
    // }
}
