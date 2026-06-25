<?php

declare(strict_types=1);

namespace App\Features\People\Contracts;

final class PersonQuery
{
    public function __construct(
        public readonly ?string $type = null,
        public readonly ?string $idNumber = null,
        public readonly ?string $search = null,
        public readonly ?bool $withUser = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {}
}
