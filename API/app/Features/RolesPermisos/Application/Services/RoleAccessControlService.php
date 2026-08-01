<?php

declare(strict_types=1);

namespace App\Features\RolesPermisos\Application\Services;

use App\Core\AppException;
use App\Features\RolesPermisos\Application\DTO\PermissionQuery;
use App\Features\RolesPermisos\Domain\Interfaces\RoleAccessControlServiceInterface;
use App\Features\RolesPermisos\Application\DTO\RoleQuery;
use App\Features\RolesPermisos\Domain\Entities\Permission;
use App\Features\RolesPermisos\Domain\Entities\Role;
use App\Features\RolesPermisos\Domain\Interfaces\PermissionRepositoryInterface;
use App\Features\RolesPermisos\Domain\Interfaces\RoleRepositoryInterface;
use App\Infrastructure\Menu\Domain\Interfaces\MenuServiceInterface;
use App\Features\User\Domain\Interfaces\UserRepositoryInterface;

class RoleAccessControlService implements RoleAccessControlServiceInterface
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private PermissionRepositoryInterface $permissionRepository,
        private MenuServiceInterface $menuService,
        private UserRepositoryInterface $userRepository,
    ) {}

    public function createRole(string $code, string $name): Role
    {
        $existing = $this->roleRepository->findByCode($code);

        if ($existing !== null) {
            throw new AppException('ROLE_ALREADY_EXISTS');
        }

        $role = new Role();
        $role->setCode($code);
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
            throw new AppException('ROLE_NOT_FOUND');
        }

        $this->roleRepository->delete($role);
    }

    public function listRoles(?RoleQuery $query = null): array
    {
        if ($query === null) {
            return $this->roleRepository->findAll();
        }

        return $this->roleRepository->findPaginated($query);
    }

    public function createPermission(string $name): Permission
    {
        $existing = $this->permissionRepository->findByName($name);

        if ($existing !== null) {
            throw new AppException('PERMISSION_ALREADY_EXISTS');
        }

        $permission = new Permission();
        $permission->setName($name);
        $permission->setCreatedAt(new \DateTime());
        $permission->setUpdatedAt(new \DateTime());

        $this->permissionRepository->save($permission);

        return $permission;
    }

    public function listPermissions(?PermissionQuery $query = null): array
    {
        if ($query === null) {
            return $this->permissionRepository->findAll();
        }

        return $this->permissionRepository->findPaginated($query);
    }

    public function assignRoleToUser(int $userId, string $roleCode): void
    {
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new AppException('USER_NOT_FOUND');
        }

        $role = $this->roleRepository->findByCode($roleCode);

        if ($role === null) {
            throw new AppException('ROLE_NOT_FOUND');
        }

        if (!$role->getUsers()->contains($user)) {
            $role->getUsers()->add($user);
            if (!$user->getRoles()->contains($role)) {
                $user->getRoles()->add($role);
            }
            $this->roleRepository->save($role);
        }
    }

    public function removeRoleFromUser(int $userId, string $roleCode): void
    {
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new AppException('USER_NOT_FOUND');
        }

        $role = $this->roleRepository->findByCode($roleCode);

        if ($role === null) {
            throw new AppException('ROLE_NOT_FOUND');
        }

        if ($role->getUsers()->contains($user)) {
            $role->getUsers()->removeElement($user);
            $user->getRoles()->removeElement($role);
            $this->roleRepository->save($role);
        }
    }

    public function assignPermissionToRole(int $roleId, string $permissionName): void
    {
        $role = $this->roleRepository->findById($roleId);

        if ($role === null) {
            throw new AppException('ROLE_NOT_FOUND');
        }

        $permission = $this->permissionRepository->findByName($permissionName);

        if ($permission === null) {
            throw new AppException('PERMISSION_NOT_FOUND');
        }

        $role->addPermission($permission);
        $this->roleRepository->save($role);
    }

    public function hasRole(int $userId, string $roleCode): bool
    {
        $roles = $this->roleRepository->findByUser($userId);

        foreach ($roles as $role) {
            if ($role->getCode() === $roleCode) {
                return true;
            }
        }

        return false;
    }

    public function getUserRoles(int $userId): array
    {
        return $this->roleRepository->findByUser($userId);
    }

    public function getRolesForUsers(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $roles = $this->roleRepository->findByUsers($userIds);
        $map = [];
        foreach ($roles as $role) {
            foreach ($role->getUsers() as $user) {
                $map[(int) $user->getId()][] = $role;
            }
        }
        return $map;
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
