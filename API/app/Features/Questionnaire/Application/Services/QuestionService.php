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
    private const DEFAULT_SELECTION_OPTIONS = [
        ['value' => '1', 'label' => 'Malo'],
        ['value' => '2', 'label' => 'Regular'],
        ['value' => '3', 'label' => 'Bueno'],
        ['value' => '4', 'label' => 'Muy Bueno'],
        ['value' => '5', 'label' => 'Excelente'],
    ];

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

        $validTypes = ['number', 'selection'];
        $type = $data['type'] ?? 'selection';
        if (!in_array($type, $validTypes, true)) {
            throw new AppException('INVALID_QUESTION_TYPE');
        }

        $question = new Question();
        $question->setSection($section);
        $question->setText($data['text']);
        $question->setType($type);
        $question->setQuestionOrder(isset($data['question_order'])
            ? (int) $data['question_order']
            : $this->repository->getMaxOrder($sectionId) + 1);
        $question->setCreatedAt(new \DateTime());
        $question->setUpdatedAt(new \DateTime());

        if ($type === 'selection') {
            $this->buildOptions($question, $data['options'] ?? null);
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
            $validTypes = ['number', 'selection'];
            if (!in_array($data['type'], $validTypes, true)) {
                throw new AppException('INVALID_QUESTION_TYPE');
            }
            $question->setType($data['type']);
        }
        if (isset($data['question_order'])) {
            $question->setQuestionOrder((int) $data['question_order']);
        }
        if (array_key_exists('options', $data)) {
            $this->replaceOptions($question, $data['options'] ?? null);
        } else {
            $this->adaptOptionsToType($question);
        }

        $question->setUpdatedAt(new \DateTime());
        $this->repository->save($question);

        return $question;
    }

    private function buildOptions(Question $question, ?array $providedOptions): void
    {
        if (is_array($providedOptions) && count($providedOptions) > 0) {
            foreach ($providedOptions as $i => $opt) {
                $option = new QuestionOption();
                if (is_array($opt)) {
                    $option->setOptionText($opt['value'] ?? (string) ($i + 1));
                    $option->setLabel($opt['label'] ?? null);
                } else {
                    $option->setOptionText((string) $opt);
                }
                $option->setOptionOrder($i);
                $question->addOption($option);
            }
            return;
        }

        foreach (self::DEFAULT_SELECTION_OPTIONS as $i => $opt) {
            $option = new QuestionOption();
            $option->setOptionText($opt['value']);
            $option->setLabel($opt['label']);
            $option->setOptionOrder($i);
            $question->addOption($option);
        }
    }

    private function replaceOptions(Question $question, ?array $providedOptions): void
    {
        foreach ($question->getOptions() as $opt) {
            $question->removeOption($opt);
        }

        if (is_array($providedOptions)) {
            $this->buildOptions($question, $providedOptions);
        }
    }

    private function adaptOptionsToType(Question $question): void
    {
        if ($question->getType() === 'selection') {
            $options = $this->repository->findOptionsIncludingDeleted($question->getId());
            if (count($options) === 0) {
                $this->buildOptions($question, null);
                return;
            }

            foreach ($options as $option) {
                $option->setDeletedAt(null);
                $question->addOption($option);
            }
            return;
        }

        foreach ($question->getOptions() as $option) {
            $option->setDeletedAt(new \DateTime());
        }
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

    public function move(int $id, string $direction): void
    {
        if (!in_array($direction, ['up', 'down'], true)) {
            throw new AppException('INVALID_ORDER_DIRECTION');
        }

        $question = $this->repository->findById($id);
        if ($question === null) {
            throw new AppException('QUESTION_NOT_FOUND');
        }

        $sectionId = $question->getSection()->getId();
        $currentOrder = $question->getQuestionOrder();

        $neighbor = $this->repository->findAdjacent($sectionId, $currentOrder, $direction);
        if ($neighbor === null) {
            return;
        }

        $question->setQuestionOrder($neighbor->getQuestionOrder());
        $neighbor->setQuestionOrder($currentOrder);
        $question->setUpdatedAt(new \DateTime());
        $neighbor->setUpdatedAt(new \DateTime());

        $this->repository->save($question);
        $this->repository->save($neighbor);
    }

    public function listBySection(int $sectionId): array
    {
        return $this->repository->findBySection($sectionId);
    }
}
