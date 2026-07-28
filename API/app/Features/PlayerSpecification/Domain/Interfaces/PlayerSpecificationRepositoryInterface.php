<?php

declare(strict_types=1);

namespace App\Features\PlayerSpecification\Domain\Interfaces;

use App\Features\PlayerSpecification\Application\DTO\PlayerSpecificationQuery;
use App\Features\PlayerSpecification\Domain\Entities\PlayerSpecification;

interface PlayerSpecificationRepositoryInterface
{
    public function findById(int $id): ?PlayerSpecification;
    public function findPaginated(PlayerSpecificationQuery $query): array;
    public function findByUserId(int $userId): ?PlayerSpecification;
    public function save(PlayerSpecification $spec): void;
    public function softDelete(PlayerSpecification $spec): void;
}
