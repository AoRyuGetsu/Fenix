<?php

declare(strict_types=1);

namespace App\Features\Auth\Contracts;

use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;

interface RoleAccessControlServiceInterface
{
    public function createRole(string $name): Role;

    public function deleteRole(int $roleId): void;

    public function listRoles(): array;

    public function createPermission(string $name): Permission;

    public function deletePermission(int $permissionId): void;

    public function listPermissions(): array;

    public function assignRoleToUser(int $userId, string $roleName): void;

    public function removeRoleFromUser(int $userId, string $roleName): void;

    public function assignPermissionToRole(int $roleId, string $permissionName): void;

    public function removePermissionFromRole(int $roleId, string $permissionName): void;

    public function hasRole(int $userId, string $roleName): bool;

    public function hasPermission(int $userId, string $permissionName): bool;

    public function getUserRoles(int $userId): array;

    public function getUserPermissions(int $userId): array;

    public function getUserMenus(int $userId): array;
}
