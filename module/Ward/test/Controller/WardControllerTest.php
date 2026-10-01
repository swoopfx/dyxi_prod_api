<?php

declare(strict_types=1);

namespace WardTest\Controller;

use PHPUnit\Framework\TestCase;
use Ward\Controller\WardController;
use Ward\Service\WardService;
use Authentication\Service\ApiAuthenticateService;
use Ward\Entity\Ward;
use Laminas\Http\Request;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;

class WardControllerTest extends TestCase
{
    private $wardService;
    private $apiAuthService;
    private $controller;

    protected function setUp(): void
    {
        $this->wardService = $this->createMock(WardService::class);
        $this->apiAuthService = $this->createMock(ApiAuthenticateService::class);
        $this->controller = new WardController($this->wardService, $this->apiAuthService);
    }

    public function testWardInfoActionReturnsAgeInMonths(): void
    {
        $identity = ['uuid' => 'user-123'];
        $this->apiAuthService->method('getContainerIdentity')->willReturn($identity);

        $ward = new Ward();
        $ward->setFullname('Little Johnny');
        $dob = (new \DateTime())->modify('-24 months');
        $ward->setDateOfBirth($dob);

        $this->wardService->expects($this->once())
            ->method('getWardInfo')
            ->with('1', $identity)
            ->willReturn($ward);

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);

        $routeMatch = new RouteMatch(['action' => 'wardInfo', 'id' => '1']);
        $event = new MvcEvent();
        $event->setRouteMatch($routeMatch);

        $this->controller->setEvent($event);
        // Inject request into controller
        $refProp = new \ReflectionProperty(\Laminas\Mvc\Controller\AbstractActionController::class, 'request');
        $refProp->setAccessible(true);
        $refProp->setValue($this->controller, $request);

        $jsonModel = $this->controller->wardInfoAction();
        $variables = $jsonModel->getVariables();

        $this->assertTrue($variables['success']);
        $this->assertSame(24, $variables['data']['age']);
        $this->assertSame(24, $variables['data']['age_in_months']);
        $this->assertSame('Little Johnny', $variables['data']['fullname']);
    }

    public function testEditWardActionReturnsUpdatedWardData(): void
    {
        $identity = ['uuid' => 'user-123'];
        $this->apiAuthService->method('getContainerIdentity')->willReturn($identity);

        $ward = new Ward();
        $ward->setFullname('Johnny Updated');
        $dob = (new \DateTime())->modify('-36 months');
        $ward->setDateOfBirth($dob);

        $this->wardService->expects($this->once())
            ->method('editWard')
            ->with(['fullname' => 'Johnny Updated', 'id' => '1'], $identity)
            ->willReturn($ward);

        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->setContent(json_encode(['fullname' => 'Johnny Updated']));

        $routeMatch = new RouteMatch(['action' => 'editWard', 'id' => '1']);
        $event = new MvcEvent();
        $event->setRouteMatch($routeMatch);

        $this->controller->setEvent($event);
        $refProp = new \ReflectionProperty(\Laminas\Mvc\Controller\AbstractActionController::class, 'request');
        $refProp->setAccessible(true);
        $refProp->setValue($this->controller, $request);

        $jsonModel = $this->controller->editWardAction();
        $variables = $jsonModel->getVariables();

        $this->assertTrue($variables['success']);
        $this->assertSame('Johnny Updated', $variables['data']['fullname']);
        $this->assertSame('Successfully updated ward Johnny Updated.', $variables['description']);
    }

    public function testEditWardActionMethodNotAllowed(): void
    {
        $request = new Request();
        $request->setMethod(Request::METHOD_GET);

        $refProp = new \ReflectionProperty(\Laminas\Mvc\Controller\AbstractActionController::class, 'request');
        $refProp->setAccessible(true);
        $refProp->setValue($this->controller, $request);

        $jsonModel = $this->controller->editWardAction();
        $variables = $jsonModel->getVariables();

        $this->assertFalse($variables['success']);
        $this->assertSame('MethodNotAllowed', $variables['error']);
    }
}
