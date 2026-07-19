<?php

declare(strict_types=1);

namespace App\Infrastructure\Menu\Application\Services;

use App\Infrastructure\Menu\Domain\Interfaces\MenuServiceInterface;
use App\Infrastructure\Menu\Domain\Interfaces\MenuRepositoryInterface;

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
