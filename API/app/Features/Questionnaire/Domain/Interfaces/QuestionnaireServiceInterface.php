<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Domain\Interfaces;

use App\Features\Questionnaire\Application\DTO\QuestionnaireQuery;

use App\Features\Questionnaire\Domain\Entities\Questionnaire;

interface QuestionnaireServiceInterface
{
    public function create(array $data): Questionnaire;
    public function update(int $id, array $data): Questionnaire;
    public function findById(int $id): ?Questionnaire;
    public function softDelete(int $id): void;
    public function list(?QuestionnaireQuery $query = null): array;
    public function findByResponderRoles(array $roleCodes, ?QuestionnaireQuery $query = null): array;
}
