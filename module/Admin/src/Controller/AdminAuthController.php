<?php declare(strict_types=1);

namespace Admin\Controller;

use Authentication\Entity\User;
use Authentication\Service\ApiAuthenticateService;
use Authentication\Service\JWTIssuer;
use Authorization\Service\AuthorizationService;
use Doctrine\ORM\EntityManager;
use Laminas\Http\Header\SetCookie;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use Ramsey\Uuid\Uuid;

class AdminAuthController extends AbstractActionController
{
    private EntityManager $entityManager;
    private ApiAuthenticateService $apiAuthenticateService;
    private JWTIssuer $jwtIssuer;
    private AuthorizationService $authorizationService;

    public function __construct(
        EntityManager $entityManager,
        ApiAuthenticateService $apiAuthenticateService,
        JWTIssuer $jwtIssuer,
        AuthorizationService $authorizationService
    ) {
        $this->entityManager = $entityManager;
        $this->apiAuthenticateService = $apiAuthenticateService;
        $this->jwtIssuer = $jwtIssuer;
        $this->authorizationService = $authorizationService;
    }

    /**
     * Renders the Admin Login page (/admin/login).
     */
    public function loginAction()
    {
        // If already authenticated as SuperAdmin, redirect to /admin
        $request = $this->getRequest();
        $cookie = $request->getCookie();
        $jwtToken = isset($cookie['admin_jwt']) ? (string) $cookie['admin_jwt'] : null;

        if ($jwtToken !== null) {
            try {
                $token = $this->jwtIssuer->parseToken($jwtToken);
                $claims = $token->claims()->get('coded');
                $roleId = (int) ($claims['role'] ?? 0);
                if ($roleId === 1000 || $roleId === 10000) {
                    return $this->redirect()->toUrl('/admin');
                }
            } catch (\Throwable $e) {
                // Invalid or expired cookie ignored
            }
        }

        $viewModel = new ViewModel();
        $viewModel->setTerminal(true);
        $viewModel->setTemplate('admin/admin-auth/login');
        return $viewModel;
    }

    /**
     * Authenticates SuperAdmin credentials via API (/api/admin/auth/login).
     */
    public function authenticateAction()
    {
        $request = $this->getEvent()->getRequest() ?? $this->getRequest();
        $method = strtoupper($request->getMethod() ?? '');
        if ($method !== 'POST') {
            return new JsonModel(['success' => false, 'error' => 'MethodNotAllowed']);
        }

        $data = json_decode($request->getContent(), true) ?: $request->getPost()->toArray();
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if (empty($email) || empty($password)) {
            return new JsonModel([
                'success' => false,
                'error' => 'MissingCredentials',
                'description' => 'Please provide both email address and password.'
            ]);
        }

        $userRepo = $this->entityManager->getRepository(User::class);
        /** @var User|null $user */
        $user = $userRepo->findOneBy(['email' => strtolower($email)]);

        if ($user === null) {
            return new JsonModel([
                'success' => false,
                'error' => 'InvalidCredentials',
                'description' => 'Invalid email address or password.'
            ]);
        }

        // Verify password hash
        if (!password_verify($password, $user->getPassword())) {
            return new JsonModel([
                'success' => false,
                'error' => 'InvalidCredentials',
                'description' => 'Invalid email address or password.'
            ]);
        }

        // Verify account state
        if ($user->getState() !== null && (int) $user->getState()->getId() !== 1 && strtolower($user->getState()->getState()) !== 'enabled') {
            return new JsonModel([
                'success' => false,
                'error' => 'AccountDisabled',
                'description' => 'Your account is disabled or pending activation.'
            ]);
        }

        // STRICT SUPERADMIN CHECK: Only SuperAdmin accounts allowed!
        $roleId = $user->getRole() ? (int) $user->getRole()->getId() : 10;
        $roleName = $user->getRole() ? strtolower($user->getRole()->getName()) : '';

        if ($roleId !== 1000 && $roleId !== 10000 && $roleName !== 'superadmin') {
            return new JsonModel([
                'success' => false,
                'error' => 'AccessDenied',
                'description' => 'Access Denied: Only SuperAdmin accounts are authorized to access the Central Admin Portal.'
            ]);
        }

        // Issue JWT Bearer Token
        $claim = [
            'uuid' => $user->getUuid(),
            'email' => $user->getEmail(),
            'role' => $roleId,
            'token_id' => Uuid::uuid4()->toString(),
        ];
        $token = $this->jwtIssuer->issueToken($claim)->toString();

        // Set admin_jwt cookie for web browser session navigation
        $cookie = new SetCookie('admin_jwt', $token, time() + 86400, '/');
        /** @var \Laminas\Http\Response $response */
        $response = $this->getResponse();
        $response->getHeaders()->addHeader($cookie);

        // Sync Redis cache for this user
        $this->authorizationService->syncAllRedisCache();

        return new JsonModel([
            'success' => true,
            'message' => 'SuperAdmin authentication successful.',
            'token' => $token,
            'redirect' => '/admin/rbac',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'role_id' => $roleId,
                'role_name' => $user->getRole() ? $user->getRole()->getName() : 'SuperAdmin',
            ]
        ]);
    }

    /**
     * Admin Logout (/admin/logout).
     */
    public function logoutAction()
    {
        $cookie = new SetCookie('admin_jwt', '', time() - 3600, '/');
        /** @var \Laminas\Http\Response $response */
        $response = $this->getResponse();
        $response->getHeaders()->addHeader($cookie);

        return $this->redirect()->toUrl('/admin/login');
    }
}
