<?php

declare(strict_types=1);

namespace GeneralTest\Controller;

use General\Controller\GeneralController;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class GeneralControllerTest extends AbstractHttpControllerTestCase
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

    public function testLegalInfoActionReturns200AndValidJson(): void
    {
        $this->dispatch('/general/api/legal-info', 'GET');
        // In local CLI test runs, database bootstrap connection errors may override the status code.
        // But the assertion checks for successful mapping and JSON rendering when the system runs.
        $this->assertResponseStatusCode(200);
        
        $responseContent = json_decode($this->getResponse()->getContent(), true);
        $this->assertTrue($responseContent['success']);
        $this->assertArrayHasKey('privacy_policy', $responseContent);
        $this->assertArrayHasKey('terms_and_conditions', $responseContent);
        $this->assertArrayHasKey('coppa_and_data_compliance', $responseContent);
        $this->assertArrayHasKey('intellectual_property', $responseContent);
        $this->assertArrayHasKey('payment_and_refund_terms', $responseContent);
        $this->assertArrayHasKey('user_generated_content_rules', $responseContent);
        $this->assertArrayHasKey('limitation_of_liability', $responseContent);
        $this->assertArrayHasKey('cookie_policy', $responseContent);
    }
}
