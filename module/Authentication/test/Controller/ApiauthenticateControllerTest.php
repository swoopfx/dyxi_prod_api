<?php

declare(strict_types=1);

namespace AuthenticateTest\Controller;

use Authentication\Controller\ApiauthenticateController;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class ApiauthenticateControllerTest extends AbstractHttpControllerTestCase
{
    public function setUp(): void
    {
        $configOverrides = [];

        $this->setApplicationConfig(ArrayUtils::merge(
            include __DIR__ . '/../../../../config/application.config.php',
            $configOverrides
        ));

        parent::setUp();
    }

    public function testDeleteUserActionWithGetMethodReturns405(): void
    {
        $this->dispatch('/auth/ipa/delete-user', 'GET');
        $this->assertResponseStatusCode(405);
        
        $responseContent = json_decode($this->getResponse()->getContent(), true);
        $this->assertFalse($responseContent['success']);
        $this->assertEquals('MethodNotAllowed', $responseContent['error']);
    }

    public function testDeleteUserActionWithoutAuthHeaderReturns401(): void
    {
        $this->dispatch('/auth/ipa/delete-user', 'DELETE');
        $this->assertResponseStatusCode(401);
        
        $responseContent = json_decode($this->getResponse()->getContent(), true);
        $this->assertFalse($responseContent['success']);
        $this->assertEquals('Unauthorized', $responseContent['error']);
    }
}
