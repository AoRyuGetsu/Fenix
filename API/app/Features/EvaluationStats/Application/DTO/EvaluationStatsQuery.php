<?php

declare(strict_types=1);

namespace App\Features\EvaluationStats\Application\DTO;

class EvaluationStatsQuery
{
    public function __construct(
        public readonly int $targetUserId,
        public readonly ?int $questionnaireId = null,
        public readonly ?array $periodIds = null,
    ) {}
}
