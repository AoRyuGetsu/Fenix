<?php

declare(strict_types=1);

namespace App\Features\Teams\Application\DTO;

final class TeamQueryDto
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {}
}
