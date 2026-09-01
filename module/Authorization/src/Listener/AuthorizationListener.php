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
        'register',
        'verify',
        'resendMobileCode',
        'resend-mobile-code',
        'refresh',
        'logout',
        'google',
        'swagger',
        'doc'
    ];

    public function __construct(AuthorizationService $authorizationService)
    {
        $this->authorizationService = $authorizationService;
    }

    public function __invoke(MvcEvent $event)
    {
        $routeMatch = $event->getRouteMatch();
        if (!$routeMatch) {
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
            if (str_contains($path, '/auth/ipa/login') || str_contains($path, '/auth/ipa/register') || str_contains($path, '/auth/google')) {
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
                    if (is_array($identity) && isset($identity['role'])) {
                        $roleId = (int) $identity['role'];
                    } elseif (is_object($identity) && isset($identity->role)) {
                        $roleId = (int) $identity->role;
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

        if (!$this->authorizationService->isGranted($roleId, $permissionName)) {
            $response = $event->getResponse();
            if (method_exists($response, 'setStatusCode')) {
                $response->setStatusCode(403);
            }

            $jsonModel = new JsonModel([
                'success' => false,
                'error' => 'Forbidden',
                'description' => "Access denied. Insufficient permissions for resource '{$permissionName}'."
            ]);

            $event->setResult($jsonModel);
            return $jsonModel;
        }
    }
}
