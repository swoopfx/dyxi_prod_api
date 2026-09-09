<?php

declare(strict_types=1);

namespace Authorization\Listener;

use Authentication\Service\ApiAuthenticateService;
use Authorization\Service\AuthorizationService;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\JsonModel;

class AuthorizationListener
{
    private AuthorizationService $authorizationService;

    // Public route actions / paths exempt from authorization checks
    private array $publicRoutes = [
        'login',
        'authenticate',
        'register',
        'verify',
        'resendMobileCode',
        'resend-mobile-code',
        'refresh',
        'logout',
        'google',
        'swagger',
        'swaggerJson',
        'swagger-json',
        'doc',
        'legalInfo',
        'legal-info',
        'legalinfo'
    ];

    public function __construct(AuthorizationService $authorizationService)
    {
        $this->authorizationService = $authorizationService;
    }

    public function __invoke(MvcEvent $event)
    {
        $routeMatch = $event->getRouteMatch();
        if (! $routeMatch) {
            return;
        }

        $action = $routeMatch->getParam('action');
        $controller = $routeMatch->getParam('controller');

        // Allow public auth endpoints and OpenAPI doc routes
        if ($action && in_array($action, $this->publicRoutes, true)) {
            return;
        }

        $request = $event->getRequest();
        if (method_exists($request, 'getUri')) {
            $path = $request->getUri()->getPath();
            if (str_contains($path, '/auth/ipa/login') || str_contains($path, '/auth/ipa/register') || str_contains($path, '/auth/google') || str_contains($path, '/api/docs') || str_contains($path, '/legal-info') || str_starts_with($path, '/admin')) {
                return;
            }
        }

        // Determine user role ID (defaults to Guest = 10)
        $roleId = 10; // Guest
        $sm = $event->getApplication()->getServiceManager();

        try {
            if ($sm->has(ApiAuthenticateService::class)) {
                /** @var ApiAuthenticateService $authService */
                $authService = $sm->get(ApiAuthenticateService::class);
                if ($authService->hasIdentity()) {
                    $identity = $authService->getIdentity();
                    if (is_array($identity)) {
                        if (isset($identity['role_id']) && is_numeric($identity['role_id'])) {
                            $roleId = (int) $identity['role_id'];
                        } elseif (isset($identity['role'])) {
                            if (is_numeric($identity['role'])) {
                                $roleId = (int) $identity['role'];
                            } else {
                                $roleName = strtolower(trim((string) $identity['role']));
                                $roleMap = [
                                    'guest' => 10,
                                    'guardian' => 100,
                                    'consultant' => 200,
                                    'admin' => 500,
                                    'superadmin' => 1000,
                                ];
                                $roleId = $roleMap[$roleName] ?? 100;
                            }
                        }
                    } elseif (is_object($identity)) {
                        if (isset($identity->role_id)) {
                            $roleId = (int) $identity->role_id;
                        } elseif (isset($identity->role)) {
                            $roleId = is_numeric($identity->role) ? (int) $identity->role : 100;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Unauthenticated request falls back to Guest role (10)
            $roleId = 10;
        }

        // Standard permission key e.g. "evaluation.create" or "ward.index"
        $module = strtolower(explode('\\', (string)$controller)[0] ?? 'application');
        $permissionName = $module . '.' . strtolower($action ?: 'index');

        if (! $this->authorizationService->isGranted($roleId, $permissionName)) {
            $response = $event->getResponse();
            $statusCode = ($roleId === 10) ? 401 : 403;
            $errorType = ($roleId === 10) ? 'Unauthorized' : 'Forbidden';
            $descriptionText = ($roleId === 10)
                ? "Authentication required. Please provide a valid Bearer token."
                : "Access denied. Insufficient permissions for resource '{$permissionName}'.";

            if (method_exists($response, 'setStatusCode')) {
                $response->setStatusCode($statusCode);
            }

            $jsonModel = new JsonModel([
                'success' => false,
                'error' => $errorType,
                'description' => $descriptionText
            ]);

            $event->setResult($jsonModel);
            return $jsonModel;
        }
    }
}
