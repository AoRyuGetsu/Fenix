<?php

declare(strict_types=1);

namespace App\Features\RolesPermisos\Domain\Interfaces;

use App\Features\RolesPermisos\Application\DTO\RoleQuery;
use App\Features\RolesPermisos\Domain\Entities\Role;

interface RoleRepositoryInterface
{
    public function findById(int $id): ?Role;

    public function findByCode(string $code): ?Role;

    public function findAll(): array;

    public function findPaginated(RoleQuery $query): array;

    public function findByUser(int $userId): array;

    /** @param int[] $userIds */
    public function findByUsers(array $userIds): array;

    public function save(Role $role): void;

    public function delete(Role $role): void;
}
