<?php

declare(strict_types=1);

namespace App\Features\People\Contracts;

use App\Features\People\Models\Person;

interface PersonServiceInterface
{
    public function create(array $data): Person;

    public function findById(int $id): ?Person;

    public function findByUserId(int $userId): ?Person;

    public function findByIdNumber(string $idNumber): ?Person;

    public function update(int $id, array $data): Person;

    public function softDelete(int $id): void;

    public function list(?PersonQuery $query = null): array;
}
