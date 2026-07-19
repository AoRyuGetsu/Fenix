<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Domain\Interfaces;

use App\Features\Questionnaire\Domain\Entities\Question;

interface QuestionServiceInterface
{
    public function create(int $sectionId, array $data): Question;
    public function update(int $id, array $data): Question;
    public function findById(int $id): ?Question;
    public function softDelete(int $id): void;
    public function listBySection(int $sectionId): array;
}
