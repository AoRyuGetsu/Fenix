<?php

declare(strict_types=1);

namespace App\Features\PlayerSpecification\Domain\Interfaces;

use App\Features\PlayerSpecification\Application\DTO\PlayerSpecificationQuery;
use App\Features\PlayerSpecification\Domain\Entities\PlayerSpecification;

interface PlayerSpecificationServiceInterface
{
    public function findById(int $id): ?PlayerSpecification;
    public function findPaginated(PlayerSpecificationQuery $query): array;
    public function create(int $userId, array $data, int $authUserId): PlayerSpecification;
    public function update(int $userId, array $data, int $authUserId): PlayerSpecification;
    public function softDelete(int $userId): void;
}
