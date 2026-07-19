<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Domain\Interfaces;

use App\Features\Questionnaire\Domain\Entities\Section;

interface SectionServiceInterface
{
    public function create(int $questionnaireId, array $data): Section;

    public function update(int $id, array $data): Section;

    public function findById(int $id): ?Section;

    public function findByQuestionnaire(int $questionnaireId): array;

    public function softDelete(int $id): void;
}
