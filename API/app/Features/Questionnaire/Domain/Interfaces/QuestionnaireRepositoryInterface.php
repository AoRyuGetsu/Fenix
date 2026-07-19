<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Domain\Interfaces;

use App\Features\Questionnaire\Application\DTO\QuestionnaireQuery;
use App\Features\Questionnaire\Domain\Entities\Questionnaire;

interface QuestionnaireRepositoryInterface
{
    public function save(Questionnaire $questionnaire): void;
    public function findById(int $id): ?Questionnaire;
    public function findPaginated(QuestionnaireQuery $query): array;
    public function findByResponderRoles(array $roleIds, ?QuestionnaireQuery $query = null): array;
    public function softDelete(Questionnaire $questionnaire): void;

    public function countAll(): int;
}
