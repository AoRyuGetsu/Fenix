<?php

declare(strict_types=1);

namespace App\Features\EvaluationStats\Application\Services;

use App\Core\AppException;
use App\Features\EvaluationStats\Application\DTO\EvaluationStatsQuery;
use App\Features\EvaluationStats\Application\DTO\TeamStatsQuery;
use App\Features\EvaluationStats\Domain\Interfaces\EvaluationStatsServiceInterface;
use App\Features\Questionnaire\Domain\Interfaces\QuestionnaireRepositoryInterface;
use App\Features\Teams\Domain\Interfaces\TeamRepositoryInterface;
use App\Features\User\Domain\Interfaces\UserRepositoryInterface;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManager;
use MathPHP\Statistics\Regression\Linear;

class EvaluationStatsService implements EvaluationStatsServiceInterface
{
    public function __construct(
        private EntityManager $entityManager,
        private UserRepositoryInterface $userRepository,
        private QuestionnaireRepositoryInterface $questionnaireRepository,
        private TeamRepositoryInterface $teamRepository,
    ) {}

    public function getStats(EvaluationStatsQuery $query): array
    {
        $user = $this->userRepository->findById($query->targetUserId);
        if ($user === null) {
            throw new AppException('USER_NOT_FOUND');
        }

        if ($query->questionnaireId !== null) {
            $questionnaire = $this->questionnaireRepository->findById($query->questionnaireId);
            if ($questionnaire === null) {
                throw new AppException('QUESTIONNAIRE_NOT_FOUND');
            }
        }

        $periods = $this->fetchPeriods();
        if (count($periods) < 2) {
            throw new AppException('EVALUATION_STATS_INSUFFICIENT_PERIODS');
        }

        $periodMap = [];
        $periodList = [];
        foreach ($periods as $i => $p) {
            $id = (int) $p['id'];
            $periodMap[$id] = [
                'id'    => $id,
                'name'  => $p['name'],
                'index' => $i + 1,
            ];
            $periodList[] = $periodMap[$id];
        }

        $rawData = $this->fetchRawAnswerData($query);

        if (empty($rawData)) {
            throw new AppException('EVALUATION_STATS_NO_DATA');
        }

        $selectionOptionMap = $this->buildSelectionOptionMap($rawData);

        $grouped = $this->groupAndResolveValues($rawData, $selectionOptionMap);

        $questionnaireTitles = $this->fetchQuestionnaireTitles(array_keys($grouped));

        $questionnaires = [];
        foreach ($grouped as $qId => $periodGroups) {
            $qTitle = $questionnaireTitles[$qId] ?? 'Cuestionario #' . $qId;

            $allQuestIds = [];
            foreach ($periodGroups as $questions) {
                foreach ($questions as $questId => $data) {
                    $allQuestIds[$questId] = $data;
                }
            }

            $perQuestion = [];
            foreach ($allQuestIds as $questId => $questData) {
                $averages = [];
                $points = [];
                foreach ($periodMap as $pId => $pInfo) {
                    if (isset($periodGroups[$pId][$questId])) {
                        $vals = $periodGroups[$pId][$questId]['values'];
                        $avg = count($vals) > 0 ? round(array_sum($vals) / count($vals), 2) : 0;
                        $count = count($vals);
                        $averages[] = [
                            'period_id'   => $pId,
                            'period_name' => $pInfo['name'],
                            'average'     => $avg,
                            'count'       => $count,
                        ];
                        $points[] = [$pInfo['index'], $avg];
                    }
                }

                $perQuestion[] = [
                    'question_id'   => $questId,
                    'question_text' => $questData['question_text'],
                    'question_type' => $questData['question_type'],
                    'averages'      => $averages,
                    'regression'    => $this->computeRegression($points),
                ];
            }

            $overallAverages = [];
            $overallPoints = [];
            foreach ($periodMap as $pId => $pInfo) {
                $allVals = [];
                foreach ($periodGroups[$pId] ?? [] as $questData) {
                    array_push($allVals, ...$questData['values']);
                }
                if ($allVals !== []) {
                    $avg = round(array_sum($allVals) / count($allVals), 2);
                    $overallAverages[] = [
                        'period_id'   => $pId,
                        'period_name' => $pInfo['name'],
                        'average'     => $avg,
                        'count'       => count($allVals),
                    ];
                    $overallPoints[] = [$pInfo['index'], $avg];
                }
            }

            $questionnaires[] = [
                'questionnaire' => [
                    'id'    => $qId,
                    'title' => $qTitle,
                ],
                'per_question' => $perQuestion,
                'overall'      => [
                    'averages'   => $overallAverages,
                    'regression' => $this->computeRegression($overallPoints),
                ],
            ];
        }

        return [
            'target_user'   => [
                'id'           => $user->getId(),
                'display_name' => $this->getUserDisplayName($user),
            ],
            'periods'        => $periodList,
            'questionnaires' => $questionnaires,
        ];
    }

    public function getTeamStats(TeamStatsQuery $query): array
    {
        $team = $this->teamRepository->findById($query->teamId);
        if ($team === null) {
            throw new AppException('TEAM_NOT_FOUND');
        }

        $periods = $this->fetchPeriods();
        $periodMap = [];
        $periodList = [];
        foreach ($periods as $i => $p) {
            $id = (int) $p['id'];
            $periodMap[$id] = [
                'id'    => $id,
                'name'  => $p['name'],
                'index' => $i + 1,
            ];
            $periodList[] = $periodMap[$id];
        }

        $playerIds = [];
        foreach ($team->getPlayers() as $player) {
            $playerIds[] = $player->getId();
        }

        if ($playerIds === []) {
            throw new AppException('EVALUATION_STATS_NO_DATA');
        }

        $rawData = $this->fetchTeamRawAnswerData($playerIds, $query);

        if (empty($rawData)) {
            throw new AppException('EVALUATION_STATS_NO_DATA');
        }

        $selectionOptionMap = $this->buildSelectionOptionMap($rawData);
        $groupedByUser = $this->groupAndResolveValuesByUser($rawData, $selectionOptionMap);

        $questionnaireTitles = $this->fetchQuestionnaireTitles(array_keys($groupedByUser));

        $questionnaires = [];
        $allTrendCounts = ['upward' => 0, 'downward' => 0, 'stable' => 0];

        foreach ($groupedByUser as $qId => $userGroups) {
            $qTitle = $questionnaireTitles[$qId] ?? 'Cuestionario #' . $qId;

            $players = [];
            $teamPeriodTotals = [];
            $teamPeriodCounts = [];

            foreach ($userGroups as $uId => $periodGroups) {
                $playerEntity = $this->userRepository->findById($uId);
                if ($playerEntity === null) {
                    continue;
                }

                $allValsByPeriod = [];
                foreach ($periodGroups as $pId => $questions) {
                    if (!isset($allValsByPeriod[$pId])) {
                        $allValsByPeriod[$pId] = [];
                    }
                    foreach ($questions as $questData) {
                        array_push($allValsByPeriod[$pId], ...$questData['values']);
                    }
                }

                $overallAverages = [];
                $overallPoints = [];
                foreach ($periodMap as $pId => $pInfo) {
                    if (isset($allValsByPeriod[$pId]) && $allValsByPeriod[$pId] !== []) {
                        $avg = round(array_sum($allValsByPeriod[$pId]) / count($allValsByPeriod[$pId]), 2);
                        $overallAverages[] = [
                            'period_id'   => $pId,
                            'period_name' => $pInfo['name'],
                            'average'     => $avg,
                            'count'       => count($allValsByPeriod[$pId]),
                        ];
                        $overallPoints[] = [$pInfo['index'], $avg];

                        if (!isset($teamPeriodTotals[$pId])) {
                            $teamPeriodTotals[$pId] = 0;
                            $teamPeriodCounts[$pId] = 0;
                        }
                        $teamPeriodTotals[$pId] += $avg;
                        $teamPeriodCounts[$pId]++;
                    }
                }

                $regression = $this->computeRegression($overallPoints);
                if ($regression !== null) {
                    $allTrendCounts[$regression['trend']]++;
                }

                $players[] = [
                    'user'             => [
                        'id'           => $uId,
                        'display_name' => $this->getUserDisplayName($playerEntity),
                    ],
                    'overall_averages' => $overallAverages,
                    'regression'       => $regression,
                ];
            }

            $teamAverages = [];
            $teamPoints = [];
            foreach ($periodMap as $pId => $pInfo) {
                if (isset($teamPeriodTotals[$pId]) && $teamPeriodCounts[$pId] > 0) {
                    $teamAvg = round($teamPeriodTotals[$pId] / $teamPeriodCounts[$pId], 2);
                    $teamAverages[] = [
                        'period_id'   => $pId,
                        'period_name' => $pInfo['name'],
                        'average'     => $teamAvg,
                    ];
                    $teamPoints[] = [$pInfo['index'], $teamAvg];
                }
            }

            $questionnaires[] = [
                'questionnaire'   => [
                    'id'    => $qId,
                    'title' => $qTitle,
                ],
                'team_averages'   => $teamAverages,
                'team_regression' => $this->computeRegression($teamPoints),
                'players'         => $players,
            ];
        }

        return [
            'team'           => [
                'id'   => $team->getId(),
                'name' => $team->getName(),
            ],
            'periods'        => $periodList,
            'trend_summary'  => $allTrendCounts,
            'questionnaires' => $questionnaires,
        ];
    }

    private function fetchPeriods(): array
    {
        $conn = $this->entityManager->getConnection();
        return $conn->executeQuery(
            "SELECT id, name, start_date
             FROM periods
             WHERE deleted_at IS NULL
             ORDER BY start_date ASC"
        )->fetchAllAssociative();
    }

    private function fetchRawAnswerData(EvaluationStatsQuery $query): array
    {
        $conn = $this->entityManager->getConnection();
        $params = ['targetUserId' => $query->targetUserId];

        $sql = "SELECT e.questionnaire_id, e.period_id, a.question_id,
                       q.text AS question_text, q.type AS question_type, a.value
                FROM evaluations e
                JOIN answers a ON a.evaluation_id = e.id
                JOIN questions q ON q.id = a.question_id
                WHERE e.target_user_id = :targetUserId
                  AND e.deleted_at IS NULL
                  AND a.deleted_at IS NULL
                  AND q.deleted_at IS NULL
                  AND q.type IN ('number', 'selection')";

        if ($query->questionnaireId !== null) {
            $sql .= " AND e.questionnaire_id = :questionnaireId";
            $params['questionnaireId'] = $query->questionnaireId;
        }

        if ($query->periodIds !== null && $query->periodIds !== []) {
            $sql .= " AND e.period_id IN (:periodIds)";
            $params['periodIds'] = $query->periodIds;
        }

        if ($query->periodIds !== null && $query->periodIds !== []) {
            return $conn->executeQuery($sql, $params, ['periodIds' => ArrayParameterType::INTEGER])->fetchAllAssociative();
        }

        return $conn->executeQuery($sql, $params)->fetchAllAssociative();
    }

    private function buildSelectionOptionMap(array $rawData): array
    {
        $selectionQIds = [];
        foreach ($rawData as $r) {
            if ($r['question_type'] === 'selection') {
                $selectionQIds[(int) $r['question_id']] = true;
            }
        }

        if ($selectionQIds === []) {
            return [];
        }

        $conn = $this->entityManager->getConnection();
        $qIds = array_keys($selectionQIds);
        $optionRows = $conn->executeQuery(
            "SELECT question_id, option_text, option_order FROM question_options WHERE question_id IN (?)",
            [$qIds],
            [ArrayParameterType::INTEGER]
        )->fetchAllAssociative();

        $optionMap = [];
        foreach ($optionRows as $or) {
            $optionMap[(int) $or['question_id']][$or['option_text']] = (int) $or['option_order'];
        }

        return $optionMap;
    }

    private function groupAndResolveValues(array $rawData, array $optionMap): array
    {
        $grouped = [];

        foreach ($rawData as $r) {
            $qId    = (int) $r['questionnaire_id'];
            $pId    = (int) $r['period_id'];
            $questId = (int) $r['question_id'];
            $type   = $r['question_type'];
            $value  = $r['value'];

            $numericVal = $this->resolveNumericValue($type, $value, $optionMap[$questId] ?? []);
            if ($numericVal === null) {
                continue;
            }

            if (!isset($grouped[$qId])) {
                $grouped[$qId] = [];
            }
            if (!isset($grouped[$qId][$pId])) {
                $grouped[$qId][$pId] = [];
            }
            if (!isset($grouped[$qId][$pId][$questId])) {
                $grouped[$qId][$pId][$questId] = [
                    'values'        => [],
                    'question_text' => $r['question_text'],
                    'question_type' => $type,
                ];
            }

            $grouped[$qId][$pId][$questId]['values'][] = $numericVal;
        }

        return $grouped;
    }

    private function resolveNumericValue(string $type, string $value, array $options): ?float
    {
        if ($type === 'number') {
            return is_numeric($value) ? (float) $value : null;
        }

        if ($type === 'selection') {
            if (is_numeric($value)) {
                return (float) $value;
            }
            if (isset($options[$value])) {
                return (float) $options[$value];
            }
            foreach ($options as $text => $order) {
                if (strcasecmp($text, $value) === 0) {
                    return (float) $order;
                }
            }
        }

        return null;
    }

    private function computeRegression(array $points): ?array
    {
        if (count($points) < 2) {
            return null;
        }

        $regression = new Linear($points);
        $params = $regression->getParameters();

        $slope = $params['m'];
        $intercept = $params['b'];
        $rSquared = $regression->coefficientOfDetermination();

        $trend = match (true) {
            $slope > 0.05  => 'upward',
            $slope < -0.05 => 'downward',
            default        => 'stable',
        };

        return [
            'slope'     => round($slope, 4),
            'intercept' => round($intercept, 4),
            'r_squared' => round($rSquared, 4),
            'trend'     => $trend,
        ];
    }

    private function fetchQuestionnaireTitles(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $conn = $this->entityManager->getConnection();
        $rows = $conn->executeQuery(
            "SELECT id, title FROM questionnaires WHERE id IN (?)",
            [$ids],
            [ArrayParameterType::INTEGER]
        )->fetchAllKeyValue();

        $result = [];
        foreach ($rows as $id => $title) {
            $result[(int) $id] = $title;
        }

        return $result;
    }

    private function fetchTeamRawAnswerData(array $playerIds, TeamStatsQuery $query): array
    {
        if ($playerIds === []) {
            return [];
        }

        $conn = $this->entityManager->getConnection();
        $params = ['playerIds' => $playerIds];

        $sql = "SELECT e.target_user_id, e.questionnaire_id, e.period_id, a.question_id,
                       q.text AS question_text, q.type AS question_type, a.value
                FROM evaluations e
                JOIN answers a ON a.evaluation_id = e.id
                JOIN questions q ON q.id = a.question_id
                WHERE e.target_user_id IN (:playerIds)
                  AND e.deleted_at IS NULL
                  AND a.deleted_at IS NULL
                  AND q.deleted_at IS NULL
                  AND q.type IN ('number', 'selection')";

        if ($query->questionnaireId !== null) {
            $sql .= " AND e.questionnaire_id = :questionnaireId";
            $params['questionnaireId'] = $query->questionnaireId;
        }

        if ($query->periodIds !== null && $query->periodIds !== []) {
            $sql .= " AND e.period_id IN (:periodIds)";
            $params['periodIds'] = $query->periodIds;
        }

        if ($query->periodIds !== null && $query->periodIds !== []) {
            return $conn->executeQuery($sql, $params, ['playerIds' => ArrayParameterType::INTEGER, 'periodIds' => ArrayParameterType::INTEGER])->fetchAllAssociative();
        }

        return $conn->executeQuery($sql, $params, ['playerIds' => ArrayParameterType::INTEGER])->fetchAllAssociative();
    }

    private function groupAndResolveValuesByUser(array $rawData, array $optionMap): array
    {
        $grouped = [];

        foreach ($rawData as $r) {
            $uId    = (int) $r['target_user_id'];
            $qId    = (int) $r['questionnaire_id'];
            $pId    = (int) $r['period_id'];
            $questId = (int) $r['question_id'];
            $type   = $r['question_type'];
            $value  = $r['value'];

            $numericVal = $this->resolveNumericValue($type, $value, $optionMap[$questId] ?? []);
            if ($numericVal === null) {
                continue;
            }

            if (!isset($grouped[$qId])) {
                $grouped[$qId] = [];
            }
            if (!isset($grouped[$qId][$uId])) {
                $grouped[$qId][$uId] = [];
            }
            if (!isset($grouped[$qId][$uId][$pId])) {
                $grouped[$qId][$uId][$pId] = [];
            }
            if (!isset($grouped[$qId][$uId][$pId][$questId])) {
                $grouped[$qId][$uId][$pId][$questId] = [
                    'values'        => [],
                    'question_text' => $r['question_text'],
                    'question_type' => $type,
                ];
            }

            $grouped[$qId][$uId][$pId][$questId]['values'][] = $numericVal;
        }

        return $grouped;
    }

    private function getUserDisplayName($user): string
    {
        $person = $user->getPerson();
        if ($person !== null) {
            $name = trim(implode(' ', array_filter([
                $person->getFirstName(),
                $person->getMiddleName(),
                $person->getFirstSurname(),
                $person->getSecondSurname(),
            ])));
            if ($name !== '') {
                return $name;
            }
        }
        return $user->getEmail();
    }
}
