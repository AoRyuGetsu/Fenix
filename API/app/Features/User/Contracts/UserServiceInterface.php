<?php

declare(strict_types=1);

namespace App\Features\User\Contracts;

use App\Features\User\Models\User;

interface UserServiceInterface
{
    public function create(string $email, string $password): User;

    public function findByEmail(string $email): ?User;

    public function findById(int $id): ?User;

    public function update(int $id, array $data): User;

    public function softDelete(int $id): void;

    public function list(?UserQuery $query = null): array;
}
