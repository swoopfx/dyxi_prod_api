<?php

declare(strict_types=1);

namespace Admin\Controller;

use Authentication\Entity\Roles;
use Authentication\Entity\User;
use Authentication\Entity\UserState;
use Authentication\Service\ApiAuthenticateService;
use Authentication\Service\JWTIssuer;
use Authorization\Service\AuthorizationService;
use Doctrine\ORM\EntityManager;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;

class RbacAdminController extends AbstractActionController
{
    private EntityManager $entityManager;
    private AuthorizationService $authorizationService;
    private ApiAuthenticateService $apiAuthenticateService;
    private JWTIssuer $jwtIssuer;

    public function __construct(
        EntityManager $entityManager,
        AuthorizationService $authorizationService,
        ApiAuthenticateService $apiAuthenticateService,
        JWTIssuer $jwtIssuer
    ) {
        $this->entityManager = $entityManager;
        $this->authorizationService = $authorizationService;
        $this->apiAuthenticateService = $apiAuthenticateService;
        $this->jwtIssuer = $jwtIssuer;
    }

    /**
     * Security check ensuring only SuperAdmin (Role 1000 / SuperAdmin) has access.
     */
    private function checkSuperAdminAuthorization(): ?Response
    {
        $identity = $this->apiAuthenticateService->getContainerIdentity();

        // Also check admin_jwt HTTP cookie if identity is null
        if (empty($identity)) {
            $request = $this->getRequest();
            $cookie = $request->getCookie();
            $jwtToken = null;

            if ($cookie !== false && isset($cookie['admin_jwt'])) {
                $jwtToken = (string) $cookie['admin_jwt'];
            } elseif (isset($_COOKIE['admin_jwt'])) {
                $jwtToken = (string) $_COOKIE['admin_jwt'];
            }

            if ($jwtToken !== null) {
                try {
                    $token = $this->jwtIssuer->parseToken($jwtToken);
                    $identity = $token->claims()->get('coded');
                } catch (\Throwable $e) {
                    $identity = null;
                }
            }
        }

        if (empty($identity)) {
            try {
                $identity = $this->apiAuthenticateService->getIdentity();
            } catch (\Throwable $th) {
                $identity = null;
            }
        }

        $isSuperAdmin = false;

        if (!empty($identity)) {
            $identity = (array) $identity;
            $coded = isset($identity['coded']) ? (array) $identity['coded'] : $identity;
            $roleId = (int) ($coded['role'] ?? $identity['role'] ?? 0);
            $email = $coded['email'] ?? $identity['email'] ?? null;
            $uuid = $coded['uuid'] ?? $identity['uuid'] ?? null;

            if ($roleId === 1000 || $roleId === 10000) {
                $isSuperAdmin = true;
            } else {
                $userRepo = $this->entityManager->getRepository(User::class);
                $user = null;
                if ($email !== null) {
                    $user = $userRepo->findOneBy(['email' => $email]);
                } elseif ($uuid !== null) {
                    $user = $userRepo->findOneBy(['uuid' => $uuid]);
                }
                if ($user !== null && $user->getRole() !== null && ($user->getRole()->getId() === 1000 || strtolower($user->getRole()->getName()) === 'superadmin')) {
                    $isSuperAdmin = true;
                }
            }
        }

        if (!$isSuperAdmin) {
            /** @var Response $response */
            $response = $this->getResponse();
            $response->setStatusCode(Response::STATUS_CODE_403);
            return $response;
        }

        return null;
    }

    /**
     * Web Dashboard view.
     */
    public function indexAction()
    {
        $authError = $this->checkSuperAdminAuthorization();
        if ($authError !== null) {
            return $this->redirect()->toUrl('/admin/login');
        }

        $viewModel = new ViewModel(['unauthorized' => false]);
        $viewModel->setTerminal(true);
        $viewModel->setTemplate('admin/rbac-admin/index');
        return $viewModel;
    }

    /**
     * Fetch complete dashboard payload (users, roles, permissions, matrix).
     */
    public function dashboardDataAction()
    {
        $authError = $this->checkSuperAdminAuthorization();
        if ($authError !== null) {
            return new JsonModel([
                'success' => false,
                'error' => 'Forbidden',
                'description' => 'Only SuperAdmin accounts have access to the RBAC Admin Interface.'
            ]);
        }

        try {
            $userRepo = $this->entityManager->getRepository(User::class);
            $usersRaw = $userRepo->findBy([], ['id' => 'ASC']);

            $usersList = [];
            foreach ($usersRaw as $u) {
                $usersList[] = [
                    'id' => $u->getId(),
                    'uuid' => $u->getUuid(),
                    'email' => $u->getEmail(),
                    'role_id' => $u->getRole() ? $u->getRole()->getId() : 10,
                    'role_name' => $u->getRole() ? $u->getRole()->getName() : 'Guest',
                    'state_id' => $u->getState() ? $u->getState()->getId() : 1,
                    'state_name' => $u->getState() ? $u->getState()->getState() : 'Enabled',
                    'email_confirmed' => (bool)$u->getEmailConfirmed(),
                    'is_profiled' => (bool)$u->getIsProfiled(),
                ];
            }

            $rolesRaw = $this->authorizationService->getAllRoles();
            $rolesList = [];
            foreach ($rolesRaw as $r) {
                $rolesList[] = [
                    'id' => $r->getId(),
                    'name' => $r->getName(),
                ];
            }

            $permissionsRaw = $this->authorizationService->getAllPermissions();
            $permissionsList = [];
            foreach ($permissionsRaw as $p) {
                $permissionsList[] = [
                    'id' => $p->getId(),
                    'name' => $p->getName(),
                    'description' => $p->getDescription(),
                ];
            }

            $matrix = $this->authorizationService->getRolePermissionMatrix();

            return new JsonModel([
                'success' => true,
                'users' => $usersList,
                'roles' => $rolesList,
                'permissions' => $permissionsList,
                'matrix' => $matrix,
            ]);
        } catch (\Throwable $th) {
            return new JsonModel([
                'success' => false,
                'error' => 'ServerError',
                'description' => $th->getMessage()
            ]);
        }
    }

    /**
     * Update a user's role and state.
     */
    public function updateUserRoleStateAction()
    {
        $authError = $this->checkSuperAdminAuthorization();
        if ($authError !== null) {
            return new JsonModel([
                'success' => false,
                'error' => 'Forbidden',
                'description' => 'Only SuperAdmin accounts have access to the RBAC Admin Interface.'
            ]);
        }

        $request = $this->getRequest();
        if (!$request->isPost()) {
            return new JsonModel(['success' => false, 'error' => 'MethodNotAllowed']);
        }

        $data = json_decode($request->getContent(), true) ?: $request->getPost()->toArray();
        $userId = (int)($data['user_id'] ?? 0);
        $roleId = isset($data['role_id']) ? (int)$data['role_id'] : null;
        $stateId = isset($data['state_id']) ? (int)$data['state_id'] : null;
        $emailConfirmed = isset($data['email_confirmed']) ? (bool)$data['email_confirmed'] : null;

        if ($userId <= 0) {
            return new JsonModel(['success' => false, 'error' => 'InvalidUserId']);
        }

        $userRepo = $this->entityManager->getRepository(User::class);
        /** @var User|null $user */
        $user = $userRepo->find($userId);

        if ($user === null) {
            return new JsonModel(['success' => false, 'error' => 'UserNotFound']);
        }

        if ($roleId !== null) {
            $roleRepo = $this->entityManager->getRepository(Roles::class);
            $roleObj = $roleRepo->find($roleId);
            if ($roleObj !== null) {
                $user->setRole($roleObj);
            }
        }

        if ($stateId !== null) {
            $stateRepo = $this->entityManager->getRepository(UserState::class);
            $stateObj = $stateRepo->find($stateId);
            if ($stateObj !== null) {
                $user->setState($stateObj);
            }
        }

        if ($emailConfirmed !== null) {
            $user->setEmailConfirmed($emailConfirmed);
        }

        $this->entityManager->flush();

        // Re-sync Redis for this user
        $this->authorizationService->syncAllRedisCache();

        return new JsonModel([
            'success' => true,
            'message' => "User '{$user->getEmail()}' updated successfully.",
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'role_id' => $user->getRole() ? $user->getRole()->getId() : 10,
                'role_name' => $user->getRole() ? $user->getRole()->getName() : 'Guest',
                'state_id' => $user->getState() ? $user->getState()->getId() : 1,
                'state_name' => $user->getState() ? $user->getState()->getState() : 'Enabled',
                'email_confirmed' => (bool)$user->getEmailConfirmed(),
            ]
        ]);
    }

    /**
     * Create a new permission definition.
     */
    public function createPermissionAction()
    {
        $authError = $this->checkSuperAdminAuthorization();
        if ($authError !== null) {
            return new JsonModel([
                'success' => false,
                'error' => 'Forbidden',
                'description' => 'Only SuperAdmin accounts have access to the RBAC Admin Interface.'
            ]);
        }

        $request = $this->getRequest();
        if (!$request->isPost()) {
            return new JsonModel(['success' => false, 'error' => 'MethodNotAllowed']);
        }

        $data = json_decode($request->getContent(), true) ?: $request->getPost()->toArray();
        $name = trim((string)($data['name'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));

        if (empty($name)) {
            return new JsonModel(['success' => false, 'error' => 'PermissionNameRequired']);
        }

        $perm = $this->authorizationService->createPermission($name, $description);

        return new JsonModel([
            'success' => true,
            'message' => "Permission '{$perm->getName()}' created successfully.",
            'permission' => [
                'id' => $perm->getId(),
                'name' => $perm->getName(),
                'description' => $perm->getDescription(),
            ]
        ]);
    }

    /**
     * Toggle a role permission.
     */
    public function toggleRolePermissionAction()
    {
        $authError = $this->checkSuperAdminAuthorization();
        if ($authError !== null) {
            return new JsonModel([
                'success' => false,
                'error' => 'Forbidden',
                'description' => 'Only SuperAdmin accounts have access to the RBAC Admin Interface.'
            ]);
        }

        $request = $this->getRequest();
        if (!$request->isPost()) {
            return new JsonModel(['success' => false, 'error' => 'MethodNotAllowed']);
        }

        $data = json_decode($request->getContent(), true) ?: $request->getPost()->toArray();
        $roleId = (int)($data['role_id'] ?? 0);
        $permissionId = (int)($data['permission_id'] ?? 0);
        $grant = (bool)($data['grant'] ?? false);

        if ($roleId <= 0 || $permissionId <= 0) {
            return new JsonModel(['success' => false, 'error' => 'InvalidArguments']);
        }

        $result = $this->authorizationService->togglePermissionForRole($roleId, $permissionId, $grant);

        return new JsonModel([
            'success' => $result,
            'message' => $result ? "Role permission toggled." : "Failed to toggle permission.",
            'matrix' => $this->authorizationService->getRolePermissionMatrix(),
        ]);
    }

    /**
     * Trigger full Redis cache synchronization.
     */
    public function syncRedisAction()
    {
        $authError = $this->checkSuperAdminAuthorization();
        if ($authError !== null) {
            return new JsonModel([
                'success' => false,
                'error' => 'Forbidden',
                'description' => 'Only SuperAdmin accounts have access to the RBAC Admin Interface.'
            ]);
        }

        $res = $this->authorizationService->syncAllRedisCache();

        return new JsonModel([
            'success' => true,
            'message' => 'Redis RBAC cache fully synchronized.',
            'details' => $res,
        ]);
    }
}
