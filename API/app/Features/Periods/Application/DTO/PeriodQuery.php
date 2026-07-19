<?php

declare(strict_types=1);

namespace App\Features\Periods\Application\DTO;

class PeriodQuery
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {}
}
