<?php

declare(strict_types=1);

namespace App\Infrastructure\Menu\Domain\Interfaces;

use App\Infrastructure\Menu\Domain\Entities\Menu;

interface MenuServiceInterface
{
    public function findByUserId(int $userId): array;
}
