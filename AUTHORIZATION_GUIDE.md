# Dyxi API - Authorization & Role-Based Access Control (RBAC) Guide

The **Authorization Module** (`module/Authorization`) provides high-performance, Redis-cached **Role-Based Access Control (RBAC)** across the entire Dyxi application.

---

## 1. System Roles Hierarchy

The platform defines 5 core roles with numeric IDs for fast comparisons and parent-child inheritance:

| Role Name | Role ID | Description | Default Permissions |
|---|---|---|---|
| **Guest** | `10` | Unauthenticated public visitors | Public auth endpoints (`/auth/*`), docs |
| **Guardian** | `100` | Self-registered parents/guardians | Ward management, assessment access, resources |
| **Consultant** | `200` | Healthcare/Educational specialists | Assigned ward evaluations, consultant portal |
| **Admin** | `500` | System administrators | Full module management, user administration |
| **SuperAdmin** | `1000` | Platform super-administrators | Wildcard (`*`) - Unrestricted global access |

> [!TIP]
> **Subsidiary & Sub-Roles**: The `Roles` entity supports parent-child relationships via the `roles_parents` database table. Any sub-role automatically inherits all permissions defined on its parent role.

---

## 2. Architecture & Redis Caching Flow

```
                               ┌──────────────────────────┐
                               │     Incoming Request     │
                               └────────────┬─────────────┘
                                            │
                               ┌────────────▼─────────────┐
                               │  AuthorizationListener   │
                               └────────────┬─────────────┘
                                            │
                               ┌────────────▼─────────────┐
                               │ Is Public Route/Action?  │
                               └──────┬────────────┬──────┘
                             YES  │            │ NO
                                  │            │
                                  ▼            ▼
                             [ ALLOW ]   Check Redis Cache
                                         (Key: role_permissions_<roleId>)
                                               │
                                      ┌────────┴────────┐
                                 CACHE HIT          CACHE MISS
                                      │                 │
                                      ▼                 ▼
                                [ Fast Return ]    Query DB & Cache
                                                        │
                                                        ▼
                                                  [ ALLOW / DENY 403 ]
```

1. **Login Priming**: Upon user login (`ApiAuthenticateService::loginAction` / `authenticateSocial`), user role permissions are automatically compiled and cached in Redis.
2. **Zero DB Latency**: Authorization checks inspect Redis first (`role_permissions_<roleId>`), bypassing database queries on hot request paths.
3. **Graceful Fallback**: If Redis is temporarily unreachable, the service falls back to querying Doctrine repositories seamlessly without crashing.

---

## 3. How to Use & Maximize the Authorization Module

### Method 1: Automatic Application-Wide Route Guarding (MvcEvent Listener)

The `AuthorizationListener` automatically guards endpoints during route resolution (`MvcEvent::EVENT_ROUTE`).

Permissions follow the standard convention: `<module_name>.<action_name>`
- Example: Requesting `/evaluation/create` evaluates permission `evaluation.create`.
- Example: Requesting `/ward/list` evaluates permission `ward.list`.

If the authenticated user's role lacks the permission, the API automatically returns `403 Forbidden`:

```json
{
    "success": false,
    "error": "Forbidden",
    "description": "Access denied. Insufficient permissions for resource 'evaluation.create'."
}
```

#### Exempting Public Endpoints:
Public actions (such as `login`, `register`, `verify`, `google`, OpenAPI documentation) are exempted in `AuthorizationListener::$publicRoutes`.

---

### Method 2: Programmatic Permission Checks in Controllers & Services

Inject `AuthorizationService` into your controllers or domain services to execute fine-grained permission logic.

#### Example: In a Domain Service (`WardService.php`)

```php
use Authorization\Service\AuthorizationService;

class WardService
{
    private AuthorizationService $authorizationService;

    public function __construct(AuthorizationService $authorizationService)
    {
        $this->authorizationService = $authorizationService;
    }

    public function deleteWard(User $user, int $wardId): bool
    {
        // 1. Check if user has explicit permission to delete wards
        if (!$this->authorizationService->isUserGranted($user, 'ward.delete')) {
            throw new \Exception("Access Denied: You do not have permission to delete wards.");
        }

        // Proceed with deletion logic...
        return true;
    }
}
```

#### Example: In a Controller Action

```php
public function exportDataAction()
{
    $authService = $this->getServiceManager()->get(\Authorization\Service\AuthorizationService::class);
    
    // Check role ID directly (e.g. Guardian = 100)
    if (!$authService->isGranted(100, 'reports.export')) {
        $this->getResponse()->setStatusCode(403);
        return new JsonModel([
            'success' => false,
            'description' => 'Exporting reports requires Guardian role or higher.'
        ]);
    }
}
```

---

### Method 3: Managing Permissions in Database & Redis Cache Invalidation

#### Adding a New Permission via Doctrine:

```php
use Authorization\Entity\Permission;
use Authorization\Entity\RolePermission;
use Authentication\Entity\Roles;

// 1. Create Permission
$permission = new Permission();
$permission->setName('adhd.assess');
$permission->setDescription('Allows conducting ADHD assessments');
$em->persist($permission);

// 2. Assign to Consultant Role (ID 200)
$consultantRole = $em->find(Roles::class, 200);
$rolePermission = new RolePermission();
$rolePermission->setRole($consultantRole);
$rolePermission->setPermission($permission);
$em->persist($rolePermission);

$em->flush();

// 3. Invalidate Redis Cache to force instant refresh
$authorizationService = $container->get(\Authorization\Service\AuthorizationService::class);
$authorizationService->clearCache();
```

---

### Method 4: Subsidiary Roles & Parent Role Inheritance

To create a specialized subsidiary role (e.g., `SeniorConsultant` extending `Consultant`):

```php
// SeniorConsultant role (ID 201) extends Consultant role (ID 200)
$consultantRole = $em->find(Roles::class, 200);

$seniorRole = new Roles();
$seniorRole->setName('SeniorConsultant');
$seniorRole->getParents()->add($consultantRole); // Inherits all Consultant permissions

$em->persist($seniorRole);
$em->flush();
```

When `$authorizationService->getPermissionsForRole(201)` is invoked, it automatically resolves and combines permissions from both `SeniorConsultant` and `Consultant` before storing the result in Redis.

---

### Method 5: Wildcards & Pattern-Matching Permission Rules

The module supports 2 powerful wildcard matchers:

1. **Global Wildcard (`*`)**: Automatically granted to `SuperAdmin` (`1000`). Passes all permission checks.
2. **Module Prefix Wildcard (`module.*`)**:
   - Granting `evaluation.*` to a role grants access to `evaluation.create`, `evaluation.view`, `evaluation.delete`, etc.

```php
// Assigning wildcard module permission
$permission = new Permission();
$permission->setName('ward.*'); // Grants all ward actions
$em->persist($permission);
```

---

## 4. Redis Environment Configuration

Configure Redis via `.env`:

```env
# Redis Cache Configuration
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_DB=0
REDIS_PASSWORD=
REDIS_TTL=86400
REDIS_NAMESPACE=dyxi_rbac
```

Clear config cache after changing configuration:
```bash
php bin/clear-config-cache.php
```

---

## 5. Multi-Namespace Redis Caching & Curriculum Services

The Redis caching framework extends beyond Authorization to support application-wide domain services with discrete Redis namespaces via `General\Service\RedisCacheService`.

### Defined Redis Namespaces:

| Namespace | Service / Domain | Purpose |
|---|---|---|
| **`dyxi_rbac`** | Authorization Module | Caches compiled role-permission arrays (`role_permissions_<roleId>`) |
| **`dyxi_curriculum`** | Curriculum Services | Caches curriculum lists and entity details with automatic hit/miss resolution |
| **`dyxi_general`** | System-wide Services | General-purpose multi-module caching |

### Curriculum Service Redis Hit/Miss Flow:

`Game\Service\CurriculumService` / `CurriculumController` utilizes `RedisCacheService` with the `dyxi_curriculum` namespace:

```php
use General\Service\RedisCacheService;

public function listCurriculums(): array
{
    return $this->entityManager->getRepository(Curriculum::class)->findAll();
}
```

#### Single Redis Cache Key Strategy (Memory Efficient):
Curriculum entities are cached under a **single unique Redis key** using the associated Ward's UUID (`curriculum_info_ward_<wardUuid>`) under the `dyxi_curriculum` namespace. This minimizes memory usage and makes invalidation straightforward:

```php
// Single unique key cached in Redis:
$redisCacheService->set('curriculum_info_ward_' . $wardUuid, $curriculum, 0, 'dyxi_curriculum');

// Dedicated lookup method by Ward UUID:
$curriculum = $curriculumService->getCurriculumByWardUuid($wardUuid);
```

#### Curriculum Recreation for Wards:
To force-recreate a curriculum for a Ward (even if a curriculum already exists for that Ward), call `$curriculumService->recreateCurriculumForWard($data)` or pass `'force_recreate' => true` in `createCurriculum($data)`:

```php
// Option A: Call dedicated recreate service method
$recreatedCurriculum = $curriculumService->recreateCurriculumForWard([
    'ward_uuid'   => 'e4d9a21b-872f-4e01-8f92-9111ab56100a',
    'name'        => 'Advanced Math & Logic',
    'description' => 'Updated curriculum for ward',
    'min_age'     => 8,
    'max_age'     => 12
]);

// Option B: API Endpoint POST /api/game/curriculum/recreate
// Payload: { "ward_uuid": "e4d9a21b-872f-4e01-8f92-9111ab56100a", "name": "Advanced Math & Logic", "force_recreate": true }
```

**Recreation Behavior**:
1. Automatically purges all existing Redis cache entries (`curriculum_info_ward_<wardUuid>`, `curriculum_filter_ward_<wardUuid>`, `curriculum_info_<id>`, etc.) under the `dyxi_curriculum` namespace.
2. Updates and refreshes entity attributes in Doctrine ORM.
3. Immediately re-primes Redis cache with fresh curriculum data and filter metadata for the Ward UUID.

#### Permanent TTL (Forever) & Dynamic TTL Override Logic:
- **Default TTL**: Configured to `0` (forever / no expiration) in [redis.global.php](file:///Applications/MAMP/htdocs/dyxi_prod_api/config/autoload/redis.global.php) and `CurriculumService::DEFAULT_CURRICULUM_CACHE_TTL`.
- **Dynamic TTL Override**: Custom TTL values can be specified during creation or recreation by passing `'ttl'` or `'cache_ttl'` in the payload:

```php
// Creating/Recreating with custom TTL (e.g. 86400 seconds = 24 hours)
$curriculum = $curriculumService->createCurriculum([
    'ward_uuid' => 'e4d9a21b-872f-4e01-8f92-9111ab56100a',
    'name'      => 'Custom TTL Curriculum',
    'ttl'       => 86400 // Custom TTL; if omitted, defaults to 0 (forever)
]);
```



