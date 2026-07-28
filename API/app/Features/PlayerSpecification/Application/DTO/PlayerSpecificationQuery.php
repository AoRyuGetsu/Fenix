<?php

declare(strict_types=1);

namespace App\Features\PlayerSpecification\Application\DTO;

class PlayerSpecificationQuery
{
    public function __construct(
        public readonly ?string $position = null,
        public readonly ?string $status = null,
        public readonly ?string $name = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {}
}
