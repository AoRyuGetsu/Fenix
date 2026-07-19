<?php

declare(strict_types=1);

namespace App\Features\RolesPermisos\Domain\Interfaces;

use App\Features\RolesPermisos\Application\DTO\PermissionQuery;
use App\Features\RolesPermisos\Application\DTO\RoleQuery;

use App\Features\RolesPermisos\Domain\Entities\Permission;
use App\Features\RolesPermisos\Domain\Entities\Role;

interface RoleAccessControlServiceInterface
{
    public function createRole(string $code, string $name): Role;

    public function deleteRole(int $roleId): void;

    public function listRoles(?RoleQuery $query = null): array;

    public function createPermission(string $name): Permission;

    public function listPermissions(?PermissionQuery $query = null): array;

    public function assignRoleToUser(int $userId, string $roleName): void;

    public function removeRoleFromUser(int $userId, string $roleName): void;

    public function assignPermissionToRole(int $roleId, string $permissionName): void;

    public function hasRole(int $userId, string $roleName): bool;

    public function getUserRoles(int $userId): array;

    /** @param int[] $userIds @return array<int, Role[]> */
    public function getRolesForUsers(array $userIds): array;

    public function getUserPermissions(int $userId): array;

    public function getUserMenus(int $userId): array;
}
