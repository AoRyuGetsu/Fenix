<?php

declare(strict_types=1);

namespace App\Features\Auth\Repositories;

use App\Features\Auth\Models\Role;

interface RoleRepositoryInterface
{
    public function findById(int $id): ?Role;

    public function findByName(string $name): ?Role;

    public function findAll(): array;

    public function findByUser(int $userId): array;

    public function save(Role $role): void;

    public function delete(Role $role): void;
}
