<?php

declare(strict_types=1);

namespace App\Features\User\Domain\Interfaces;

use App\Features\User\Application\DTO\UserQuery;

use App\Features\User\Domain\Entities\User;

interface UserServiceInterface
{
    public function create(string $email, string $password, ?array $personData = null): User;

    public function findByEmail(string $email): ?User;

    public function findById(int $id): ?User;

    public function update(int $id, array $data): User;

    public function softDelete(int $id): void;

    public function restore(int $id): void;

    public function list(?UserQuery $query = null): array;
}
