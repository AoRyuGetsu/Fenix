<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Application\Services;

use App\Core\AppException;
use App\Features\Questionnaire\Domain\Entities\Section;
use App\Features\Questionnaire\Domain\Interfaces\QuestionnaireRepositoryInterface;
use App\Features\Questionnaire\Domain\Interfaces\SectionRepositoryInterface;
use App\Features\Questionnaire\Domain\Interfaces\SectionServiceInterface;

class SectionService implements SectionServiceInterface
{
    public function __construct(
        private SectionRepositoryInterface $repository,
        private QuestionnaireRepositoryInterface $questionnaireRepository,
    ) {}

    public function create(int $questionnaireId, array $data): Section
    {
        $questionnaire = $this->questionnaireRepository->findById($questionnaireId);
        if ($questionnaire === null) {
            throw new AppException('QUESTIONNAIRE_NOT_FOUND');
        }

        $maxOrder = $this->repository->getMaxOrder($questionnaireId);

        $section = new Section();
        $section->setQuestionnaire($questionnaire);
        $section->setTitle($data['title']);
        $section->setDescription($data['description'] ?? null);
        $section->setSectionOrder($data['section_order'] ?? $maxOrder + 1);
        $section->setCreatedAt(new \DateTime());
        $section->setUpdatedAt(new \DateTime());

        $this->repository->save($section);

        return $section;
    }

    public function update(int $id, array $data): Section
    {
        $section = $this->repository->findById($id);
        if ($section === null) {
            throw new AppException('SECTION_NOT_FOUND');
        }

        if (isset($data['title'])) {
            $section->setTitle($data['title']);
        }
        if (array_key_exists('description', $data)) {
            $section->setDescription($data['description']);
        }
        if (isset($data['section_order'])) {
            $section->setSectionOrder((int) $data['section_order']);
        }

        $section->setUpdatedAt(new \DateTime());
        $this->repository->save($section);

        return $section;
    }

    public function findById(int $id): ?Section
    {
        return $this->repository->findById($id);
    }

    public function findByQuestionnaire(int $questionnaireId): array
    {
        return $this->repository->findByQuestionnaire($questionnaireId);
    }

    public function softDelete(int $id): void
    {
        $section = $this->repository->findById($id);
        if ($section === null) {
            throw new AppException('SECTION_NOT_FOUND');
        }
        $this->repository->softDelete($section);
    }
}
