<?php
declare(strict_types=1);

namespace Authorization\Entity;

use Authentication\Entity\Roles;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="role_permissions")
 */
class RolePermission
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @var Roles
     * @ORM\ManyToOne(targetEntity="Authentication\Entity\Roles")
     * @ORM\JoinColumn(name="role_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private Roles $role;

    /**
     * @var Permission
     * @ORM\ManyToOne(targetEntity="Authorization\Entity\Permission")
     * @ORM\JoinColumn(name="permission_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private Permission $permission;

    public function getId(): int
    {
        return $this->id;
    }

    public function getRole(): Roles
    {
        return $this->role;
    }

    public function setRole(Roles $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getPermission(): Permission
    {
        return $this->permission;
    }

    public function setPermission(Permission $permission): self
    {
        $this->permission = $permission;
        return $this;
    }
}
