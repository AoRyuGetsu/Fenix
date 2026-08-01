<?php

declare(strict_types=1);

namespace App\Features\Parentescos\Domain\Interfaces;

use App\Features\Parentescos\Application\DTO\ParentescoQuery;
use App\Features\Parentescos\Domain\Entities\Parentesco;

interface ParentescoRepositoryInterface
{
    public function findAll(): array;

    public function findPaginated(ParentescoQuery $query): array;

    public function findByCode(string $code): ?Parentesco;

    public function findById(int $id): ?Parentesco;
}
