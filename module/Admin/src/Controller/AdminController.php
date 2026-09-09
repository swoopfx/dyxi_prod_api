<?php declare(strict_types=1);

namespace Admin\Controller;

use Authentication\Entity\User;
use Authentication\Service\ApiAuthenticateService;
use Authentication\Service\JWTIssuer;
use Authorization\Service\AuthorizationService;
use Doctrine\ORM\EntityManager;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;

class AdminController extends AbstractActionController
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
                    file_put_contents('data/admin_auth_error.log', "Error parsing token: " . $e->getMessage() . "\n", FILE_APPEND);
                    $identity = null;
                }
            }
        }

        if (empty($identity)) {
            try {
                $identity = $this->apiAuthenticateService->getIdentity();
            } catch (\Throwable $th) {
                file_put_contents('data/admin_auth_error.log', "Error in getIdentity: " . $th->getMessage() . "\n", FILE_APPEND);
                $identity = null;
            }
        }
        
        file_put_contents('data/admin_auth_error.log', "Identity before checks: " . json_encode($identity) . "\n", FILE_APPEND);

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
     * Central Admin Command Center view served at /admin.
     */
    public function indexAction()
    {
        $authError = $this->checkSuperAdminAuthorization();
        if ($authError !== null) {
            return $this->redirect()->toUrl('/admin/login');
        }

        $userRepo = $this->entityManager->getRepository(User::class);
        $totalUsers = count($userRepo->findAll());
        $rolesCount = count($this->authorizationService->getAllRoles());
        $permissionsCount = count($this->authorizationService->getAllPermissions());

        $viewModel = new ViewModel([
            'unauthorized' => false,
            'stats' => [
                'total_users' => $totalUsers,
                'total_roles' => $rolesCount,
                'total_permissions' => $permissionsCount,
                'redis_status' => 'ONLINE',
            ]
        ]);
        $viewModel->setTerminal(true);
        $viewModel->setTemplate('admin/admin/index');
        return $viewModel;
    }

    /**
     * Central System Overview API endpoint.
     */
    public function systemOverviewAction()
    {
        $authError = $this->checkSuperAdminAuthorization();
        if ($authError !== null) {
            return new JsonModel([
                'success' => false,
                'error' => 'Forbidden',
                'description' => 'Only SuperAdmin accounts have access to the Central Admin Interface.'
            ]);
        }

        $userRepo = $this->entityManager->getRepository(User::class);
        $totalUsers = count($userRepo->findAll());
        $rolesCount = count($this->authorizationService->getAllRoles());
        $permissionsCount = count($this->authorizationService->getAllPermissions());

        return new JsonModel([
            'success' => true,
            'system' => [
                'app_name' => 'DYXI Enterprise Platform',
                'php_version' => PHP_VERSION,
                'total_users' => $totalUsers,
                'total_roles' => $rolesCount,
                'total_permissions' => $permissionsCount,
                'redis_status' => 'ONLINE',
            ]
        ]);
    }
}
