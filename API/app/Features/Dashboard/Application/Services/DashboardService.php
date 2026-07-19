<?php

declare(strict_types=1);

namespace App\Features\Dashboard\Application\Services;

use App\Features\Dashboard\Domain\Interfaces\DashboardServiceInterface;
use App\Features\Periods\Domain\Interfaces\PeriodRepositoryInterface;
use App\Features\Questionnaire\Domain\Interfaces\EvaluationRepositoryInterface;
use App\Features\Questionnaire\Domain\Interfaces\QuestionnaireRepositoryInterface;
use App\Features\User\Domain\Interfaces\UserRepositoryInterface;
use App\Infrastructure\Auth\AuthContext;

class DashboardService implements DashboardServiceInterface
{
    public function __construct(
        private QuestionnaireRepositoryInterface $questionnaireRepository,
        private UserRepositoryInterface $userRepository,
        private EvaluationRepositoryInterface $evaluationRepository,
        private PeriodRepositoryInterface $periodRepository,
    ) {}

    public function getStats(AuthContext $authContext): array
    {
        $roleCodes = $authContext->getRoles();
        $isAdmin = in_array('ADM', $roleCodes, true);

        $stats = [
            'questionnaires_total' => $this->countQuestionnaires($roleCodes),
            'users_by_role' => [],
            'my_evaluations_total' => 0,
            'evaluations_by_period' => [],
        ];

        if ($isAdmin || in_array('SUP', $roleCodes, true) || in_array('ENT', $roleCodes, true)) {
            $stats['evaluations_by_period'] = $this->evaluationRepository->countByPeriod();
        }

        $stats['users_by_role'] = $this->buildUsersByRole($roleCodes);

        $stats['my_evaluations_total'] = $this->evaluationRepository->countByResponderUser($authContext->getUserId());

        return $stats;
    }

    private function countQuestionnaires(array $roleCodes): int
    {
        return $this->questionnaireRepository->countAll();
    }

    private function buildUsersByRole(array $roleCodes): array
    {
        $allRoles = ['ADM', 'SUP', 'ENT', 'JUG', 'REP'];
        $result = [];

        foreach ($allRoles as $role) {
            $result[$role] = $this->userRepository->countByRole($role);
        }

        return $result;
    }
}
