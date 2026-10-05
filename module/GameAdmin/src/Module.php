<?php

/**
 * ============================================================================
 * DYXI Platform - GameAdmin Subsystem
 * File: Module.php
 * ============================================================================
 * Description: Main Module bootstrap class for GameAdmin. Configures event listeners
 * on MvcEvent::EVENT_DISPATCH to enforce authentication guards (Admin/SuperAdmin)
 * and automatically set the self-contained GameAdmin layout across all routes.
 * ============================================================================
 */

declare(strict_types=1);

namespace GameAdmin;

use Laminas\Mvc\MvcEvent;
use Laminas\Session\Container;

/**
 * Main GameAdmin Module class.
 */
class Module
{
    /**
     * Returns module configuration array from config/module.config.php.
     *
     * @return array
     */
    public function getConfig(): array
    {
        /** @var array $config */
        $config = include __DIR__ . '/../config/module.config.php';
        return $config;
    }

    /**
     * Bootstraps event listeners for the GameAdmin module.
     *
     * @param MvcEvent $e Framework MVC event instance.
     */
    public function onBootstrap(MvcEvent $e): void
    {
        $application  = $e->getApplication();
        $eventManager = $application->getEventManager();

        // Attach dispatch listener with high priority (100) to intercept requests before controller execution
        $eventManager->attach(MvcEvent::EVENT_DISPATCH, [$this, 'onDispatch'], 100);
    }

    /**
     * Dispatch Event Listener Callback.
     *
     * Responsibilities:
     * 1. Inspects the dispatched controller namespace. If not in GameAdmin\Controller, exits early.
     * 2. Allows unauthenticated public access ONLY to the login action (game-admin-login).
     * 3. Validates that the active session container ('GameAdmin') has authenticated = true.
     * 4. Enforces role security: verifies role_id >= 500 or role_name matching 'admin' / 'superadmin'.
     * 5. Redirects unauthorized browser requests to /game-admin/login (or 401 for API routes).
     * 6. Dynamically sets the self-contained layout template ('layout/game-admin').
     *
     * @param MvcEvent $e
     */
    public function onDispatch(MvcEvent $e): void
    {
        $routeMatch = $e->getRouteMatch();
        if (!$routeMatch) {
            return;
        }

        $controller = $routeMatch->getParam('controller', '');
        $action     = $routeMatch->getParam('action', '');
        $routeName  = $routeMatch->getMatchedRouteName();

        // Target check: Execute guard only if controller belongs to GameAdmin\Controller namespace
        if (!is_string($controller) || !str_starts_with($controller, 'GameAdmin\\Controller\\')) {
            return;
        }

        // Allow public unauthenticated access to login page
        if ($action === 'login' || $routeName === 'game-admin-login') {
            return;
        }

        // Validate session authentication
        $session         = new Container('GameAdmin');
        $isAuthenticated = isset($session->authenticated) && $session->authenticated === true;
        
        $roleId   = (int) ($session->user['role_id'] ?? 0);
        $roleName = strtolower((string) ($session->user['role_name'] ?? ''));

        // Enforce role authorization (Role ID >= 500 = Admin, >= 1000 = SuperAdmin)
        $isAdminOrSuperAdmin = $roleId >= 500 
            || $roleName === 'admin' 
            || $roleName === 'superadmin' 
            || $roleName === 'super admin'
            || str_contains($roleName, 'admin');

        // Redirect or abort if unauthorized
        if (!$isAuthenticated || !$isAdminOrSuperAdmin) {
            $router   = $e->getRouter();
            $url      = $router->assemble([], ['name' => 'game-admin-login']);
            $response = $e->getResponse();
            
            // Return JSON HTTP 401 response for API endpoints
            if ($routeName === 'api-game-admin-stats') {
                $response->setStatusCode(401);
                $response->setContent(json_encode([
                    'success' => false,
                    'error'   => 'Unauthorized',
                    'message' => 'GameAdmin module requires an authenticated Admin or Super Admin account.'
                ]));
                return;
            }

            // Redirect web browser to login page
            $response->getHeaders()->addHeaderLine('Location', $url);
            $response->setStatusCode(302);
            $response->sendHeaders();
            exit;
        }

        // Force self-contained GameAdmin layout template
        $viewModel = $e->getViewModel();
        $viewModel->setTemplate('layout/game-admin');
    }
}
