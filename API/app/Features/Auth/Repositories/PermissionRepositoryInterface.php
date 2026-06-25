<?php

declare(strict_types=1);

namespace App\Features\Auth\Repositories;

use App\Features\Auth\Models\Permission;

interface PermissionRepositoryInterface
{
    public function findById(int $id): ?Permission;

    public function findByName(string $name): ?Permission;

    public function findAll(): array;

    public function findByRole(int $roleId): array;

    public function findByUser(int $userId): array;

    public function save(Permission $permission): void;

    public function delete(Permission $permission): void;
}
