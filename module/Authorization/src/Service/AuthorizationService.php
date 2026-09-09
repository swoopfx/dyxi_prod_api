<?php

declare(strict_types=1);

namespace Authorization\Service;

use Authentication\Entity\Roles;
use Authentication\Entity\User;
use Authorization\Entity\Permission;
use Authorization\Entity\RolePermission;
use Doctrine\ORM\EntityManager;
use Laminas\Cache\Storage\StorageInterface;

class AuthorizationService
{
    private EntityManager $entityManager;
    private ?StorageInterface $cacheStorage;

    public function __construct(EntityManager $entityManager, ?StorageInterface $cacheStorage = null)
    {
        $this->entityManager = $entityManager;
        $this->cacheStorage = $cacheStorage;
    }

    /**
     * Cache user or role permissions in Redis.
     */
    public function cacheRolePermissions(int $roleId, array $permissions): void
    {
        if ($this->cacheStorage !== null) {
            $key = "role_permissions_" . $roleId;
            try {
                $this->cacheStorage->setItem($key, $permissions);
            } catch (\Throwable $e) {
                // Ignore cache write errors gracefully
            }
        }
    }

    /**
     * Clear all cached RBAC permissions.
     */
    public function clearCache(): void
    {
        if ($this->cacheStorage !== null) {
            try {
                $this->cacheStorage->flush();
            } catch (\Throwable $e) {
                // Ignore flush error
            }
        }
    }

    /**
     * Get list of permission names for a given role ID.
     * CHECKS REDIS CACHE FIRST BEFORE DATABASE QUERY.
     */
    public function getPermissionsForRole(int $roleId): array
    {
        $cacheKey = "role_permissions_" . $roleId;

        // 1. Check Redis cache first
        if ($this->cacheStorage !== null) {
            try {
                if ($this->cacheStorage->hasItem($cacheKey)) {
                    $cached = $this->cacheStorage->getItem($cacheKey);
                    if (is_array($cached)) {
                        return $cached;
                    }
                }
            } catch (\Throwable $e) {
                // Fallback to DB if cache storage throws an exception
            }
        }

        // 2. Fetch from Database
        $permissions = [];
        try {
            $roleRepo = $this->entityManager->getRepository(Roles::class);
            /** @var Roles|null $role */
            $role = $roleRepo->find($roleId);

            if ($role !== null) {
                $roleNameLower = strtolower($role->getName());
                // SuperAdmin and Admin get wildcard access
                if ($role->getId() === 1000 || $role->getId() === 500 || $roleNameLower === 'superadmin' || $roleNameLower === 'admin') {
                    $permissions = ['*'];
                } else {
                    $rolePermissionRepo = $this->entityManager->getRepository(RolePermission::class);
                    $rpEntities = $rolePermissionRepo->findBy(['role' => $role]);

                    foreach ($rpEntities as $rp) {
                        $permissions[] = strtolower($rp->getPermission()->getName());
                    }

                    // Default fallback permissions for standard user roles if DB permissions table is empty
                    if (empty($permissions) && ($roleNameLower === 'guardian' || $roleNameLower === 'consultant' || in_array($role->getId(), [2, 3, 100, 200], true))) {
                        $permissions = [
                            'ward.*',
                            'adhd.*',
                            'dyslexia.*',
                            'dyscalculia.*',
                            'wallet.*',
                            'resources.*',
                            'general.*',
                            'evaluation.*',
                            'game.*'
                        ];
                    }

                    // Also include permissions from parent roles if any exist
                    $parents = $role->getParents();
                    if ($parents) {
                        foreach ($parents as $parentRole) {
                            $parentPerms = $this->getPermissionsForRole($parentRole->getId());
                            $permissions = array_unique(array_merge($permissions, $parentPerms));
                        }
                    }
                }
            } else {
                // Fallback if role entity ID is numeric constant e.g. 100, 200, 500, 1000
                if (in_array($roleId, [500, 1000], true)) {
                    $permissions = ['*'];
                } elseif (in_array($roleId, [2, 3, 100, 200], true)) {
                    $permissions = [
                        'ward.*',
                        'adhd.*',
                        'dyslexia.*',
                        'dyscalculia.*',
                        'wallet.*',
                        'resources.*',
                        'general.*',
                        'evaluation.*',
                        'game.*'
                    ];
                }
            }
        } catch (\Throwable $e) {
            if (in_array($roleId, [500, 1000], true)) {
                $permissions = ['*'];
            } elseif (in_array($roleId, [2, 3, 100, 200], true)) {
                $permissions = [
                    'ward.*',
                    'adhd.*',
                    'dyslexia.*',
                    'dyscalculia.*',
                    'wallet.*',
                    'resources.*',
                    'general.*',
                    'evaluation.*',
                    'game.*'
                ];
            }
        }

        // 3. Store in Redis cache
        $this->cacheRolePermissions($roleId, $permissions);

        return $permissions;
    }

    /**
     * Check if a role ID has a specific permission.
     */
    public function isGranted(int $roleId, string $permissionName): bool
    {
        $permissions = $this->getPermissionsForRole($roleId);

        if (in_array('*', $permissions, true)) {
            return true;
        }

        $target = strtolower(trim($permissionName));
        if (in_array($target, $permissions, true)) {
            return true;
        }

        // Support module prefix matching e.g. "evaluation.*" matching "evaluation.create"
        foreach ($permissions as $perm) {
            if (str_ends_with($perm, '.*')) {
                $prefix = substr($perm, 0, -2);
                if (str_starts_with($target, $prefix . '.')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if a given User entity is granted a specific permission.
     */
    public function isUserGranted(?User $user, string $permissionName): bool
    {
        if ($user === null || $user->getRole() === null) {
            // Guest role fallback (ID 10)
            return $this->isGranted(10, $permissionName);
        }

        return $this->isGranted($user->getRole()->getId(), $permissionName);
    }

    /**
     * Get all registered system permissions.
     */
    public function getAllPermissions(): array
    {
        return $this->entityManager->getRepository(Permission::class)->findBy([], ['name' => 'ASC']);
    }

    /**
     * Get all system roles.
     */
    public function getAllRoles(): array
    {
        return $this->entityManager->getRepository(Roles::class)->findBy([], ['id' => 'ASC']);
    }

    /**
     * Get full role-permission mapping for matrix view.
     */
    public function getRolePermissionMatrix(): array
    {
        $matrix = [];
        $roles = $this->getAllRoles();
        $rolePermissionRepo = $this->entityManager->getRepository(RolePermission::class);

        foreach ($roles as $role) {
            $rpList = $rolePermissionRepo->findBy(['role' => $role]);
            $permIds = [];
            foreach ($rpList as $rp) {
                if ($rp->getPermission() !== null) {
                    $permIds[] = $rp->getPermission()->getId();
                }
            }
            $matrix[$role->getId()] = $permIds;
        }

        return $matrix;
    }

    /**
     * Toggle a permission for a role (grant or revoke).
     */
    public function togglePermissionForRole(int $roleId, int $permissionId, bool $grant): bool
    {
        $role = $this->entityManager->getRepository(Roles::class)->find($roleId);
        $permission = $this->entityManager->getRepository(Permission::class)->find($permissionId);

        if ($role === null || $permission === null) {
            return false;
        }

        $rpRepo = $this->entityManager->getRepository(RolePermission::class);
        $existing = $rpRepo->findOneBy(['role' => $role, 'permission' => $permission]);

        if ($grant && $existing === null) {
            $rp = new RolePermission();
            $rp->setRole($role);
            $rp->setPermission($permission);
            $this->entityManager->persist($rp);
            $this->entityManager->flush();
        } elseif (!$grant && $existing !== null) {
            $this->entityManager->remove($existing);
            $this->entityManager->flush();
        }

        // Re-cache permissions in Redis for this role
        $this->cacheStorage?->removeItem("role_permissions_" . $roleId);
        $this->getPermissionsForRole($roleId);

        return true;
    }

    /**
     * Create a new permission.
     */
    public function createPermission(string $name, ?string $description = null): Permission
    {
        $name = strtolower(trim($name));
        $permRepo = $this->entityManager->getRepository(Permission::class);
        $existing = $permRepo->findOneBy(['name' => $name]);

        if ($existing !== null) {
            return $existing;
        }

        $perm = new Permission();
        $perm->setName($name);
        $perm->setDescription($description ?: 'System permission ' . $name);
        $this->entityManager->persist($perm);
        $this->entityManager->flush();

        return $perm;
    }

    /**
     * Sync all Redis cache for roles and users.
     */
    public function syncAllRedisCache(): array
    {
        $syncedRoles = 0;
        $syncedUsers = 0;

        $roles = $this->getAllRoles();
        foreach ($roles as $role) {
            $this->cacheStorage?->removeItem("role_permissions_" . $role->getId());
            $this->getPermissionsForRole($role->getId());
            $syncedRoles++;
        }

        $users = $this->entityManager->getRepository(User::class)->findAll();
        foreach ($users as $user) {
            if ($user->getEmail() && $this->cacheStorage !== null) {
                $roleId = $user->getRole() ? $user->getRole()->getId() : 10;
                $perms = $this->getPermissionsForRole($roleId);
                $payload = [
                    'uuid' => $user->getUuid(),
                    'email' => $user->getEmail(),
                    'role_id' => $roleId,
                    'permissions' => $perms,
                    'is_profiled' => (bool)$user->getIsProfiled(),
                ];
                try {
                    $this->cacheStorage->setItem("user_authorization_" . $user->getEmail(), $payload);
                    $this->cacheStorage->setItem("user_permissions_" . $user->getEmail(), $perms);
                    $syncedUsers++;
                } catch (\Throwable $e) {
                    // Ignore cache write error
                }
            }
        }

        return [
            'roles_synced' => $syncedRoles,
            'users_synced' => $syncedUsers,
        ];
    }
}
