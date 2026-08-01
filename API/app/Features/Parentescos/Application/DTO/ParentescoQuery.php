<?php

declare(strict_types=1);

namespace App\Features\Parentescos\Application\DTO;

final class ParentescoQuery
{
    public function __construct(
        public readonly ?string $code = null,
        public readonly ?string $description = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {}
}
