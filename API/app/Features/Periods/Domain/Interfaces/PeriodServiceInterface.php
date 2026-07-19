<?php

declare(strict_types=1);

namespace App\Features\Periods\Domain\Interfaces;

use App\Features\Periods\Application\DTO\PeriodQuery;
use App\Features\Periods\Domain\Entities\Period;

interface PeriodServiceInterface
{
    public function findAll(): array;
    public function findById(int $id): ?Period;
    public function findPaginated(PeriodQuery $query): array;
    public function create(string $name, string $startDate, string $endDate): Period;
    public function update(int $id, string $name, string $startDate, string $endDate): Period;
    public function softDelete(int $id): void;
}
