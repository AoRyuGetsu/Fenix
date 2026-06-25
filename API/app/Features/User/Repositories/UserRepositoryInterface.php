<?php

declare(strict_types=1);

namespace App\Features\User\Repositories;

use App\Features\User\Models\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function findAll(array $criteria = []): array;

    public function save(User $user): void;

    public function softDelete(User $user): void;
}
