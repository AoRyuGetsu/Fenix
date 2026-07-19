<?php

declare(strict_types=1);

namespace App\Features\People\Domain\Interfaces;

use App\Features\People\Application\DTO\PersonQuery;

use App\Features\People\Domain\Entities\Person;

interface PersonServiceInterface
{
    public function create(array $data): Person;

    public function findById(int $id): ?Person;

    public function update(int $id, array $data): Person;

    public function softDelete(int $id): void;

    public function list(?PersonQuery $query = null): array;
}
