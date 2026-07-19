<?php

declare(strict_types=1);

namespace App\Features\People\Domain\Interfaces;

use App\Features\People\Application\DTO\PeopleTypeQuery;

use App\Features\People\Domain\Entities\PeopleType;

interface PeopleTypeServiceInterface
{
    public function list(?PeopleTypeQuery $query = null): array;

    public function findByCode(string $code): ?PeopleType;
}
