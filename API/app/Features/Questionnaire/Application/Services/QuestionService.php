<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Application\Services;

use App\Core\AppException;
use App\Features\Questionnaire\Application\DTO\QuestionQuery;
use App\Features\Questionnaire\Domain\Interfaces\QuestionRepositoryInterface;
use App\Features\Questionnaire\Domain\Interfaces\QuestionServiceInterface;
use App\Features\Questionnaire\Domain\Interfaces\SectionRepositoryInterface;
use App\Features\Questionnaire\Domain\Entities\Question;
use App\Features\Questionnaire\Domain\Entities\QuestionOption;

class QuestionService implements QuestionServiceInterface
{
    public function __construct(
        private QuestionRepositoryInterface $repository,
        private SectionRepositoryInterface $sectionRepository,
    ) {}

    public function create(int $sectionId, array $data): Question
    {
        $section = $this->sectionRepository->findById($sectionId);
        if ($section === null) {
            throw new AppException('SECTION_NOT_FOUND');
        }

        $validTypes = ['text', 'number', 'selection'];
        $type = $data['type'] ?? 'text';
        if (!in_array($type, $validTypes, true)) {
            throw new AppException('INVALID_QUESTION_TYPE');
        }

        $question = new Question();
        $question->setSection($section);
        $question->setText($data['text']);
        $question->setType($type);
        $question->setQuestionOrder((int) ($data['question_order'] ?? 0));
        $question->setCreatedAt(new \DateTime());
        $question->setUpdatedAt(new \DateTime());

        if ($type === 'selection' && isset($data['options']) && is_array($data['options'])) {
            foreach ($data['options'] as $i => $optText) {
                $option = new QuestionOption();
                $option->setOptionText($optText);
                $option->setOptionOrder($i);
                $question->addOption($option);
            }
        }

        $this->repository->save($question);

        return $question;
    }

    public function update(int $id, array $data): Question
    {
        $question = $this->repository->findById($id);
        if ($question === null) {
            throw new AppException('QUESTION_NOT_FOUND');
        }

        if (isset($data['text'])) {
            $question->setText($data['text']);
        }
        if (isset($data['type'])) {
            $validTypes = ['text', 'number', 'selection'];
            if (!in_array($data['type'], $validTypes, true)) {
                throw new AppException('INVALID_QUESTION_TYPE');
            }
            $question->setType($data['type']);
        }
        if (isset($data['question_order'])) {
            $question->setQuestionOrder((int) $data['question_order']);
        }
        if (isset($data['options']) && is_array($data['options'])) {
            foreach ($question->getOptions()->toArray() as $opt) {
                $question->removeOption($opt);
            }
            foreach ($data['options'] as $i => $optText) {
                $option = new QuestionOption();
                $option->setOptionText($optText);
                $option->setOptionOrder($i);
                $question->addOption($option);
            }
        }

        $question->setUpdatedAt(new \DateTime());
        $this->repository->save($question);

        return $question;
    }

    public function findById(int $id): ?Question
    {
        return $this->repository->findById($id);
    }

    public function findPaginated(QuestionQuery $query): array
    {
        return $this->repository->findPaginated($query);
    }

    public function softDelete(int $id): void
    {
        $question = $this->repository->findById($id);
        if ($question === null) {
            throw new AppException('QUESTION_NOT_FOUND');
        }
        $this->repository->softDelete($question);
    }

    public function listBySection(int $sectionId): array
    {
        return $this->repository->findBySection($sectionId);
    }
}
