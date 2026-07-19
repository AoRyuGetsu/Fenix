<?php

declare(strict_types=1);

namespace App\Features\People\Application\DTO;

final class PeopleTypeQuery
{
    public function __construct(
        public readonly ?string $code = null,
        public readonly ?string $description = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {}
}
