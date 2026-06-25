<?php

declare(strict_types=1);

namespace App\Features\Menu\Contracts;

use App\Features\Menu\Models\Menu;

interface MenuServiceInterface
{
    public function findByUserId(int $userId): array;
}
