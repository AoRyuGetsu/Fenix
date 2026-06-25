<?php

declare(strict_types=1);

namespace App\Features\Auth\Services;

use App\Features\Auth\Contracts\RoleAccessControlServiceInterface;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Repositories\PermissionRepositoryInterface;
use App\Features\Menu\Contracts\MenuServiceInterface;
use App\Features\Auth\Repositories\RoleRepositoryInterface;
use App\Features\User\Contracts\UserServiceInterface;

class RoleAccessControlService implements RoleAccessControlServiceInterface
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private PermissionRepositoryInterface $permissionRepository,
        private MenuServiceInterface $menuService,
        private UserServiceInterface $userService,
    ) {}

    public function createRole(string $name): Role
    {
        $existing = $this->roleRepository->findByName($name);

        if ($existing !== null) {
            throw new \RuntimeException("Role '{$name}' already exists");
        }

        $role = new Role();
        $role->setName($name);
        $role->setCreatedAt(new \DateTime());
        $role->setUpdatedAt(new \DateTime());

        $this->roleRepository->save($role);

        return $role;
    }

    public function deleteRole(int $roleId): void
    {
        $role = $this->roleRepository->findById($roleId);

        if ($role === null) {
            throw new \RuntimeException('Role not found');
        }

        $this->roleRepository->delete($role);
    }

    public function listRoles(): array
    {
        return $this->roleRepository->findAll();
    }

    public function createPermission(string $name): Permission
    {
        $existing = $this->permissionRepository->findByName($name);

        if ($existing !== null) {
            throw new \RuntimeException("Permission '{$name}' already exists");
        }

        $permission = new Permission();
        $permission->setName($name);
        $permission->setCreatedAt(new \DateTime());
        $permission->setUpdatedAt(new \DateTime());

        $this->permissionRepository->save($permission);

        return $permission;
    }

    public function deletePermission(int $permissionId): void
    {
        $permission = $this->permissionRepository->findById($permissionId);

        if ($permission === null) {
            throw new \RuntimeException('Permission not found');
        }

        $this->permissionRepository->delete($permission);
    }

    public function listPermissions(): array
    {
        return $this->permissionRepository->findAll();
    }

    public function assignRoleToUser(int $userId, string $roleName): void
    {
        $user = $this->userService->findById($userId);

        if ($user === null) {
            throw new \RuntimeException('User not found');
        }

        $role = $this->roleRepository->findByName($roleName);

        if ($role === null) {
            throw new \RuntimeException("Role '{$roleName}' not found");
        }

        if (!$role->getUsers()->contains($user)) {
            $role->getUsers()->add($user);
            $this->roleRepository->save($role);
        }
    }

    public function removeRoleFromUser(int $userId, string $roleName): void
    {
        $user = $this->userService->findById($userId);

        if ($user === null) {
            throw new \RuntimeException('User not found');
        }

        $role = $this->roleRepository->findByName($roleName);

        if ($role === null) {
            throw new \RuntimeException("Role '{$roleName}' not found");
        }

        if ($role->getUsers()->contains($user)) {
            $role->getUsers()->removeElement($user);
            $this->roleRepository->save($role);
        }
    }

    public function assignPermissionToRole(int $roleId, string $permissionName): void
    {
        $role = $this->roleRepository->findById($roleId);

        if ($role === null) {
            throw new \RuntimeException('Role not found');
        }

        $permission = $this->permissionRepository->findByName($permissionName);

        if ($permission === null) {
            throw new \RuntimeException("Permission '{$permissionName}' not found");
        }

        $role->addPermission($permission);
        $this->roleRepository->save($role);
    }

    public function removePermissionFromRole(int $roleId, string $permissionName): void
    {
        $role = $this->roleRepository->findById($roleId);

        if ($role === null) {
            throw new \RuntimeException('Role not found');
        }

        $permission = $this->permissionRepository->findByName($permissionName);

        if ($permission === null) {
            throw new \RuntimeException("Permission '{$permissionName}' not found");
        }

        $role->removePermission($permission);
        $this->roleRepository->save($role);
    }

    public function hasRole(int $userId, string $roleName): bool
    {
        $roles = $this->roleRepository->findByUser($userId);

        foreach ($roles as $role) {
            if ($role->getName() === $roleName) {
                return true;
            }
        }

        return false;
    }

    public function hasPermission(int $userId, string $permissionName): bool
    {
        $permissions = $this->permissionRepository->findByUser($userId);

        foreach ($permissions as $permission) {
            if ($permission->getName() === $permissionName) {
                return true;
            }
        }

        return false;
    }

    public function getUserRoles(int $userId): array
    {
        return $this->roleRepository->findByUser($userId);
    }

    public function getUserPermissions(int $userId): array
    {
        return $this->permissionRepository->findByUser($userId);
    }

    public function getUserMenus(int $userId): array
    {
        return $this->menuService->findByUserId($userId);
    }
}
