<?php

declare(strict_types=1);

namespace App\Features\User\Contracts;

final class UserQuery
{
    public function __construct(
        public readonly ?string $email = null,
        public readonly ?\DateTime $createdAfter = null,
        public readonly ?\DateTime $createdBefore = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {}
}
