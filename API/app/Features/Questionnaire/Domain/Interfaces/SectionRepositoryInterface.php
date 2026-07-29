<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Domain\Interfaces;

use App\Features\Questionnaire\Domain\Entities\Section;

use App\Features\Questionnaire\Application\DTO\SectionQuery;

interface SectionRepositoryInterface
{
    public function findById(int $id): ?Section;

    public function findByQuestionnaire(int $questionnaireId): array;

    public function findPaginated(SectionQuery $query): array;

    public function save(Section $section): void;

    public function softDelete(Section $section): void;

    public function getMaxOrder(int $questionnaireId): int;
}
