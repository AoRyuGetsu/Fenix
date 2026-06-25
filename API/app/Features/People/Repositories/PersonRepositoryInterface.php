<?php

declare(strict_types=1);

namespace App\Features\People\Repositories;

use App\Features\People\Models\Person;

interface PersonRepositoryInterface
{
    public function findById(int $id): ?Person;

    public function findByUserId(int $userId): ?Person;

    public function findByIdNumber(string $idNumber): ?Person;

    public function findAll(array $criteria = []): array;

    public function save(Person $person): void;

    public function softDelete(Person $person): void;
}
