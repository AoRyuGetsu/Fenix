<?php

declare(strict_types=1);

namespace App\Features\People\Domain\Interfaces;

use App\Features\People\Application\DTO\PeopleTypeQuery;
use App\Features\People\Domain\Entities\PeopleType;

interface PeopleTypeRepositoryInterface
{
    public function findAll(): array;

    public function findPaginated(PeopleTypeQuery $query): array;

    public function findByCode(string $code): ?PeopleType;
}
