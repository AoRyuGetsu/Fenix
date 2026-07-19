<?php

declare(strict_types=1);

namespace App\Features\RolesPermisos\Domain\Interfaces;

use App\Features\RolesPermisos\Application\DTO\PermissionQuery;
use App\Features\RolesPermisos\Domain\Entities\Permission;

interface PermissionRepositoryInterface
{
    public function findById(int $id): ?Permission;

    public function findByName(string $name): ?Permission;

    public function findAll(): array;

    public function findPaginated(PermissionQuery $query): array;

    public function findByUser(int $userId): array;

    public function save(Permission $permission): void;

    public function delete(Permission $permission): void;
}
