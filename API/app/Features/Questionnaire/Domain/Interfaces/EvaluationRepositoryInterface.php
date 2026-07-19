<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Domain\Interfaces;

use App\Features\Questionnaire\Application\DTO\EvaluationQuery;
use App\Features\Questionnaire\Domain\Entities\Evaluation;

interface EvaluationRepositoryInterface
{
    public function save(Evaluation $evaluation): void;
    public function findById(int $id): ?Evaluation;
    public function findPaginated(EvaluationQuery $query): array;
    public function softDelete(Evaluation $evaluation): void;
    public function removeAnswer(\App\Features\Questionnaire\Domain\Entities\Answer $answer): void;

    public function countByResponderUser(int $userId): int;

    public function countByPeriod(): array;
}
