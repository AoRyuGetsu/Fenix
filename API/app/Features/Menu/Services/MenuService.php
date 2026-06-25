<?php

declare(strict_types=1);

namespace App\Features\Menu\Services;

use App\Features\Menu\Contracts\MenuServiceInterface;
use App\Features\Menu\Repositories\MenuRepositoryInterface;

class MenuService implements MenuServiceInterface
{
    public function __construct(
        private MenuRepositoryInterface $repository,
    ) {}

    public function findByUserId(int $userId): array
    {
        return $this->repository->findByUserId($userId);
    }
}
