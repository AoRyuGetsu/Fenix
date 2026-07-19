<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Domain\Interfaces;

use App\Features\Questionnaire\Application\DTO\EvaluationQuery;
use App\Features\Questionnaire\Domain\Entities\Evaluation;

interface EvaluationServiceInterface
{
    public function create(int $questionnaireId, int $targetUserId, int $periodId, array $answers): Evaluation;
    public function update(int $evaluationId, array $answers): Evaluation;
    public function findById(int $id): ?Evaluation;
    public function list(?EvaluationQuery $query = null): array;
    public function softDelete(int $id): void;
}
