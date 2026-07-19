<?php

declare(strict_types=1);

namespace App\Features\Periods\Domain\Interfaces;

use App\Features\Periods\Application\DTO\PeriodQuery;
use App\Features\Periods\Domain\Entities\Period;

interface PeriodRepositoryInterface
{
    public function findAll(): array;
    public function findById(int $id): ?Period;
    public function findPaginated(PeriodQuery $query): array;
    public function findByName(string $name): ?Period;
    public function save(Period $period): void;
    public function softDelete(Period $period): void;
}
