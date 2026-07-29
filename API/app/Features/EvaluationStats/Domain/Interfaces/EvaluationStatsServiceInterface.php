<?php

declare(strict_types=1);

namespace App\Features\EvaluationStats\Domain\Interfaces;

use App\Features\EvaluationStats\Application\DTO\EvaluationStatsQuery;
use App\Features\EvaluationStats\Application\DTO\TeamStatsQuery;

interface EvaluationStatsServiceInterface
{
    public function getStats(EvaluationStatsQuery $query): array;

    public function getTeamStats(TeamStatsQuery $query): array;
}
