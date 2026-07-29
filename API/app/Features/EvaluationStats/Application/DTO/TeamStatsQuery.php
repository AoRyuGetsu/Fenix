<?php

declare(strict_types=1);

namespace App\Features\EvaluationStats\Application\DTO;

class TeamStatsQuery
{
    public function __construct(
        public readonly int $teamId,
        public readonly ?int $questionnaireId = null,
        public readonly ?array $periodIds = null,
    ) {}
}
