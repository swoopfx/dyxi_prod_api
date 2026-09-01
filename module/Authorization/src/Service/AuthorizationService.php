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
                // SuperAdmin gets wildcard access
                if ($role->getId() === 1000 || strtolower($role->getName()) === 'superadmin') {
                    $permissions = ['*'];
                } else {
                    $rolePermissionRepo = $this->entityManager->getRepository(RolePermission::class);
                    $rpEntities = $rolePermissionRepo->findBy(['role' => $role]);

                    foreach ($rpEntities as $rp) {
                        $permissions[] = strtolower($rp->getPermission()->getName());
                    }

                    // Also include permissions from parent roles if any exist (Subsidiary roles support)
                    $parents = $role->getParents();
                    if ($parents) {
                        foreach ($parents as $parentRole) {
                            $parentPerms = $this->getPermissionsForRole($parentRole->getId());
                            $permissions = array_unique(array_merge($permissions, $parentPerms));
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Log or handle DB fetch error
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
}
