<?php

declare(strict_types=1);

namespace App\Features\Representatives\Application\DTO;

class RepPlayerQuery
{
    public function __construct(
        public readonly int $repUserId,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {}
}
