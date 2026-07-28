<?php

declare(strict_types=1);

namespace App\Features\User\Application\DTO;

final class UserQuery
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?bool $active = null,
        public readonly ?\DateTime $createdAfter = null,
        public readonly ?\DateTime $createdBefore = null,
        public readonly ?string $role = null,
        public readonly ?bool $includeDeleted = null,
        public readonly ?array $teamIds = null,
        public readonly ?bool $unassigned = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {}
}
