<?php

declare(strict_types=1);

namespace App\Features\Dashboard\Domain\Interfaces;

use App\Infrastructure\Auth\AuthContext;

interface DashboardServiceInterface
{
    public function getStats(AuthContext $authContext): array;
}
