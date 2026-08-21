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

    public function testSocialLoginActionWithGetMethodReturns405(): void
    {
        $this->dispatch('/auth/ipa/social-login', 'GET');
        $this->assertResponseStatusCode(405);

        $responseContent = json_decode($this->getResponse()->getContent(), true);
        $this->assertFalse($responseContent['success']);
    }

    public function testSocialLoginActionWithoutRequiredParamsReturns400(): void
    {
        $this->dispatch('/auth/ipa/social-login', 'POST', []);
        $this->assertResponseStatusCode(400);

        $responseContent = json_decode($this->getResponse()->getContent(), true);
        $this->assertFalse($responseContent['success']);
        $this->assertEquals('SocialLoginError', $responseContent['error']);
    }

    public function testAppleInitiateActionWithoutIdTokenRedirectsToApple(): void
    {
        $this->dispatch('/auth/ipa/apple-initiate', 'GET');
        // It redirects (302) to Apple OAuth authorize page
        $this->assertResponseStatusCode(302);
        $this->assertHasResponseHeader('Location');
        $location = $this->getResponse()->getHeaders()->get('Location')->getFieldValue();
        $this->assertStringStartsWith('https://appleid.apple.com/auth/authorize', $location);
    }

    public function testGoogleInitiateActionRedirectsToGoogle(): void
    {
        $this->dispatch('/auth/ipa/google-initiate', 'GET');
        // It redirects (302) to Google OAuth authorize page
        $this->assertResponseStatusCode(302);
        $this->assertHasResponseHeader('Location');
        $location = $this->getResponse()->getHeaders()->get('Location')->getFieldValue();
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth', $location);
    }

    public function testGoogleCallbackActionWithoutParamsReturns400(): void
    {
        $this->dispatch('/auth/ipa/google-callback', 'GET');
        $this->assertResponseStatusCode(400);

        $responseContent = json_decode($this->getResponse()->getContent(), true);
        $this->assertFalse($responseContent['success']);
        $this->assertEquals('GoogleCallbackError', $responseContent['error']);
    }

    public function testGoogleActionWithGetMethodReturns405(): void
    {
        $this->dispatch('/auth/google', 'GET');
        $this->assertResponseStatusCode(405);

        $responseContent = json_decode($this->getResponse()->getContent(), true);
        $this->assertFalse($responseContent['success']);
        $this->assertEquals('MethodNotAllowed', $responseContent['error']);
    }

    public function testGoogleActionWithoutRequiredParamsReturns400(): void
    {
        $this->dispatch('/auth/google', 'POST', []);
        $this->assertResponseStatusCode(400);

        $responseContent = json_decode($this->getResponse()->getContent(), true);
        $this->assertFalse($responseContent['success']);
    }
}

