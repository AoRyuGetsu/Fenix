<?php

declare(strict_types=1);

namespace App\Features\Parentescos\Application\Services;

use App\Features\Parentescos\Application\DTO\ParentescoQuery;
use App\Features\Parentescos\Domain\Entities\Parentesco;
use App\Features\Parentescos\Domain\Interfaces\ParentescoRepositoryInterface;
use App\Features\Parentescos\Domain\Interfaces\ParentescoServiceInterface;

class ParentescoService implements ParentescoServiceInterface
{
    public function __construct(
        private ParentescoRepositoryInterface $repository,
    ) {}

    public function list(?ParentescoQuery $query = null): array
    {
        if ($query === null) {
            return $this->repository->findAll();
        }

        return $this->repository->findPaginated($query);
    }

    public function findByCode(string $code): ?Parentesco
    {
        return $this->repository->findByCode($code);
    }

    public function findById(int $id): ?Parentesco
    {
        return $this->repository->findById($id);
    }
}
