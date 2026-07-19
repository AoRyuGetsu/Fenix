<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Application\DTO;

class EvaluationQuery
{
    public function __construct(
        public readonly ?int $responderUserId = null,
        public readonly ?int $questionnaireId = null,
        public readonly ?int $targetUserId = null,
        public readonly ?int $periodId = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {}
}
