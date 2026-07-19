<?php

declare(strict_types=1);

namespace App\Features\People\Application\Services;

use App\Features\People\Application\DTO\PeopleTypeQuery;
use App\Features\People\Domain\Interfaces\PeopleTypeServiceInterface;
use App\Features\People\Domain\Entities\PeopleType;
use App\Features\People\Domain\Interfaces\PeopleTypeRepositoryInterface;

class PeopleTypeService implements PeopleTypeServiceInterface
{
    public function __construct(
        private PeopleTypeRepositoryInterface $repository,
    ) {}

    public function list(?PeopleTypeQuery $query = null): array
    {
        if ($query === null) {
            return $this->repository->findAll();
        }

        return $this->repository->findPaginated($query);
    }

    public function findByCode(string $code): ?PeopleType
    {
        return $this->repository->findByCode($code);
    }
}
