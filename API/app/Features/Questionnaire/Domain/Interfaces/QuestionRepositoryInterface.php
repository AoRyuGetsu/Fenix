<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Domain\Interfaces;

use App\Features\Questionnaire\Application\DTO\QuestionQuery;
use App\Features\Questionnaire\Domain\Entities\Question;
use App\Features\Questionnaire\Domain\Entities\QuestionOption;

interface QuestionRepositoryInterface
{
    public function save(Question $question): void;
    public function findById(int $id): ?Question;
    public function findBySection(int $sectionId): array;
    public function findPaginated(QuestionQuery $query): array;
    public function getMaxOrder(int $sectionId): int;
    public function findAdjacent(int $sectionId, int $order, string $direction): ?Question;
    public function softDelete(Question $question): void;
    /** @return QuestionOption[] incluyendo opciones soft-deleteadas */
    public function findOptionsIncludingDeleted(int $questionId): array;
}
