<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Application\Services;

use App\Core\AppException;
use App\Features\Questionnaire\Application\DTO\EvaluationQuery;
use App\Features\Questionnaire\Domain\Entities\Answer;
use App\Features\Questionnaire\Domain\Entities\Evaluation;
use App\Features\Questionnaire\Domain\Interfaces\EvaluationRepositoryInterface;
use App\Features\Questionnaire\Domain\Interfaces\EvaluationServiceInterface;
use App\Features\Periods\Domain\Interfaces\PeriodRepositoryInterface;
use App\Features\Questionnaire\Domain\Interfaces\QuestionRepositoryInterface;
use App\Features\Questionnaire\Domain\Interfaces\QuestionnaireRepositoryInterface;
use App\Features\User\Domain\Interfaces\UserRepositoryInterface;
use App\Infrastructure\Auth\AuthContext;

class EvaluationService implements EvaluationServiceInterface
{
    public function __construct(
        private EvaluationRepositoryInterface $repository,
        private PeriodRepositoryInterface $periodRepository,
        private QuestionnaireRepositoryInterface $questionnaireRepository,
        private QuestionRepositoryInterface $questionRepository,
        private UserRepositoryInterface $userRepository,
        private AuthContext $authContext,
    ) {}

    public function create(int $questionnaireId, int $targetUserId, int $periodId, array $answers): Evaluation
    {
        $questionnaire = $this->questionnaireRepository->findById($questionnaireId);
        if ($questionnaire === null) {
            throw new AppException('QUESTIONNAIRE_NOT_FOUND');
        }

        $targetUser = $this->userRepository->findById($targetUserId);
        if ($targetUser === null) {
            throw new AppException('USER_NOT_FOUND');
        }

        $period = $this->periodRepository->findById($periodId);
        if ($period === null) {
            throw new AppException('PERIOD_NOT_FOUND');
        }

        $responderUser = $this->userRepository->findById($this->authContext->getUserId());

        $evaluation = new Evaluation();
        $evaluation->setQuestionnaire($questionnaire);
        $evaluation->setResponderUser($responderUser);
        $evaluation->setTargetUser($targetUser);
        $evaluation->setPeriod($period);
        $evaluation->setCreatedAt(new \DateTime());
        $evaluation->setUpdatedAt(new \DateTime());

        foreach ($answers as $answerData) {
            $questionId = (int) ($answerData['question_id'] ?? 0);
            $question = $this->questionRepository->findById($questionId);
            if ($question === null) {
                throw new AppException('QUESTION_NOT_FOUND');
            }

            $answer = new Answer();
            $answer->setEvaluation($evaluation);
            $answer->setQuestion($question);
            $answer->setValue((string) ($answerData['value'] ?? ''));
            $answer->setCreatedAt(new \DateTime());
            $answer->setUpdatedAt(new \DateTime());
            $evaluation->addAnswer($answer);
        }

        $this->repository->save($evaluation);

        return $evaluation;
    }

    public function update(int $evaluationId, array $answers): Evaluation
    {
        $evaluation = $this->repository->findById($evaluationId);
        if ($evaluation === null) {
            throw new AppException('EVALUATION_NOT_FOUND');
        }

        foreach ($evaluation->getAnswers() as $answer) {
            $this->repository->removeAnswer($answer);
        }
        $evaluation->getAnswers()->clear();

        foreach ($answers as $answerData) {
            $questionId = (int) ($answerData['question_id'] ?? 0);
            $question = $this->questionRepository->findById($questionId);
            if ($question === null) {
                throw new AppException('QUESTION_NOT_FOUND');
            }

            $answer = new Answer();
            $answer->setEvaluation($evaluation);
            $answer->setQuestion($question);
            $answer->setValue((string) ($answerData['value'] ?? ''));
            $answer->setCreatedAt(new \DateTime());
            $answer->setUpdatedAt(new \DateTime());
            $evaluation->addAnswer($answer);
        }

        $evaluation->setUpdatedAt(new \DateTime());
        $this->repository->save($evaluation);

        return $evaluation;
    }

    public function findById(int $id): ?Evaluation
    {
        return $this->repository->findById($id);
    }

    public function list(?EvaluationQuery $query = null): array
    {
        return $this->repository->findPaginated($query ?? new EvaluationQuery());
    }

    public function softDelete(int $id): void
    {
        $evaluation = $this->repository->findById($id);
        if ($evaluation === null) {
            throw new AppException('EVALUATION_NOT_FOUND');
        }
        $this->repository->softDelete($evaluation);
    }
}
