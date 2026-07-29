<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Application\DTO;

class SectionQuery
{
    public function __construct(
        public readonly int $questionnaireId,
        public readonly ?string $title = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {}
}
