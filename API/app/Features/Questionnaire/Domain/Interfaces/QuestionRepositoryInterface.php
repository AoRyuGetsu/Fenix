<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Domain\Interfaces;

use App\Features\Questionnaire\Application\DTO\QuestionQuery;
use App\Features\Questionnaire\Domain\Entities\Question;

interface QuestionRepositoryInterface
{
    public function save(Question $question): void;
    public function findById(int $id): ?Question;
    public function findBySection(int $sectionId): array;
    public function findPaginated(QuestionQuery $query): array;
    public function softDelete(Question $question): void;
}
