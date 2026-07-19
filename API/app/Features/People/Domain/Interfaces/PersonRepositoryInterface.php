<?php

declare(strict_types=1);

namespace App\Features\People\Domain\Interfaces;

use App\Features\People\Application\DTO\PersonQuery;
use App\Features\People\Domain\Entities\Person;

interface PersonRepositoryInterface
{
    public function findById(int $id): ?Person;

    public function findByDocumentNumber(string $documentNumber): ?Person;

    public function findPaginated(PersonQuery $query): array;

    public function save(Person $person): void;

    public function softDelete(Person $person): void;
}
