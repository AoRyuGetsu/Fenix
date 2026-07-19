<?php

declare(strict_types=1);

namespace App\Features\User\Domain\Interfaces;

use App\Features\User\Application\DTO\UserQuery;
use App\Features\User\Domain\Entities\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function findPaginated(UserQuery $query): array;

    public function save(User $user): void;

    public function softDelete(User $user): void;

    public function countByRole(string $roleCode): int;
}
