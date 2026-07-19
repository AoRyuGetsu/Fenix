<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Application\DTO;

class QuestionnaireQuery
{
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?bool $active = null,
        public readonly ?int $responderRoleId = null,
        public readonly ?int $targetRoleId = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {}
}
