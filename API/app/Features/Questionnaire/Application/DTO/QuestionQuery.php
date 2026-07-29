<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Application\DTO;

class QuestionQuery
{
    public function __construct(
        public readonly int $sectionId,
        public readonly ?string $text = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {}
}
