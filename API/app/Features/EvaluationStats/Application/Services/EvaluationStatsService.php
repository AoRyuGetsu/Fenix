<?php

declare(strict_types=1);

namespace App\Features\EvaluationStats\Application\Services;

use App\Core\AppException;
use App\Features\EvaluationStats\Application\DTO\EvaluationStatsQuery;
use App\Features\EvaluationStats\Application\DTO\TeamStatsQuery;
use App\Features\EvaluationStats\Domain\Interfaces\EvaluationStatsServiceInterface;
use App\Features\Questionnaire\Domain\Interfaces\QuestionnaireRepositoryInterface;
use App\Features\PlayerSpecification\Domain\Interfaces\PlayerSpecificationRepositoryInterface;
use App\Features\Teams\Domain\Interfaces\TeamRepositoryInterface;
use App\Features\User\Domain\Interfaces\UserRepositoryInterface;
use App\Infrastructure\Auth\AuthContext;
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
        private PlayerSpecificationRepositoryInterface $playerSpecificationRepository,
        private AuthContext $authContext,
    ) {}

    public function getStats(EvaluationStatsQuery $query): array
    {
        $this->assertCanViewStats($query->targetUserId);

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

        $selectionOptionMap = $this->buildSelectionOptionMap($rawData);
        $sessionsByQuestionnaire = $this->groupBySession($rawData, $selectionOptionMap);

        $questionnaireTitles = $this->fetchQuestionnaireTitles(array_keys($sessionsByQuestionnaire));

        $questionnaires = [];
        foreach ($sessionsByQuestionnaire as $qId => $sessions) {
            $qTitle = $questionnaireTitles[$qId] ?? 'Cuestionario #' . $qId;

            $reference = $this->findEarliestDate($sessions);

            $questionMeta = [];
            foreach ($sessions as $session) {
                foreach ($session['answers'] as $questId => $answer) {
                    $questionMeta[$questId] = [
                        'question_text' => $answer['question_text'],
                        'question_type' => $answer['question_type'],
                    ];
                }
            }

            $perQuestion = [];
            foreach ($questionMeta as $questId => $meta) {
                $points = [];
                $observations = [];
                $obsIndex = 0;
                foreach ($sessions as $session) {
                    if (!isset($session['answers'][$questId])) {
                        continue;
                    }
                    $obsIndex++;
                    $x = $this->daysBetween($reference, $session['evaluated_at']);
                    $points[] = [$x, $session['answers'][$questId]['value']];
                    $observations[] = [
                        'index'        => $obsIndex,
                        'evaluated_at' => $session['evaluated_at']->format('Y-m-d H:i:s'),
                        'x_days'       => $x,
                        'value'        => $session['answers'][$questId]['value'],
                    ];
                }

                $regression = $this->computeRegression($points);
                $this->attachObservations($observations, $regression);

                $averages = $this->periodAveragesFromSessions($sessions, $periodMap, $questId);
                $perQuestion[] = [
                    'question_id'         => $questId,
                    'question_text'       => $meta['question_text'],
                    'question_type'       => $meta['question_type'],
                    'averages'            => $averages,
                    'observations'        => $observations,
                    'regression'          => $regression,
                    'historial_regression' => $this->regressionFromAverages($averages, $periodMap),
                ];
            }

            $overallPoints = [];
            $overallObservations = [];
            $overallObsIndex = 0;
            foreach ($sessions as $session) {
                $values = array_column($session['answers'], 'value');
                if ($values === []) {
                    continue;
                }
                $overallObsIndex++;
                $avg = round(array_sum($values) / count($values), 2);
                $x = $this->daysBetween($reference, $session['evaluated_at']);
                $overallPoints[] = [$x, $avg];
                $overallObservations[] = [
                    'index'        => $overallObsIndex,
                    'evaluated_at' => $session['evaluated_at']->format('Y-m-d H:i:s'),
                    'x_days'       => $x,
                    'value'        => $avg,
                ];
            }

            $overallRegression = $this->computeRegression($overallPoints);
            $this->attachObservations($overallObservations, $overallRegression);

            $overallAverages = $this->overallPeriodAveragesFromSessions($sessions, $periodMap);

            $lastObservation = null;
            $lastObservationAt = null;
            $lastObservationEvaluatorId = null;
            foreach ($sessions as $session) {
                $obs = trim((string) ($session['observaciones'] ?? ''));
                if ($obs === '') {
                    continue;
                }
                if ($lastObservationAt === null || $session['evaluated_at'] > $lastObservationAt) {
                    $lastObservationAt = $session['evaluated_at'];
                    $lastObservation = $obs;
                    $lastObservationEvaluatorId = $session['responder_user_id'] ?? null;
                }
            }

            $lastEvaluator = null;
            if ($lastObservationEvaluatorId !== null) {
                $evaluatorUser = $this->userRepository->findById($lastObservationEvaluatorId);
                if ($evaluatorUser !== null) {
                    $lastEvaluator = $this->getUserDisplayName($evaluatorUser);
                }
            }

            $questionnaires[] = [
                'questionnaire' => [
                    'id'    => $qId,
                    'title' => $qTitle,
                ],
                'per_question' => $perQuestion,
                'overall'      => [
                    'averages'            => $overallAverages,
                    'observations'        => $overallObservations,
                    'regression'          => $overallRegression,
                    'historial_regression' => $this->regressionFromAverages($overallAverages, $periodMap),
                ],
                'last_observation'     => $lastObservation,
                'last_observation_at'  => $lastObservationAt !== null ? $lastObservationAt->format('Y-m-d') : null,
                'last_evaluator'       => $lastEvaluator,
            ];
        }

        $playerSpec = $this->playerSpecificationRepository->findByUserId($user->getId());
        $team = $this->teamRepository->findByPlayerId($user->getId());

        return [
            'target_user'   => [
                'id'           => $user->getId(),
                'display_name' => $this->getUserDisplayName($user),
                'email'        => $user->getEmail(),
                'birth_date'   => $playerSpec !== null && $playerSpec->getBirthDate() !== null
                    ? $playerSpec->getBirthDate()->format('Y-m-d')
                    : null,
                'height_cm'    => $playerSpec !== null ? (float) $playerSpec->getHeightCm() : null,
                'weight_kg'    => $playerSpec !== null ? (float) $playerSpec->getWeightKg() : null,
                'team'         => $team !== null ? $team->getName() : null,
            ],
            'periods'        => $periodList,
            'questionnaires' => $questionnaires,
        ];
    }

    public function getTeamStats(TeamStatsQuery $query): array
    {
        $this->assertCanViewTeamStats($query->teamId);

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
                'days'  => $p['days'],
            ];
            $periodList[] = $periodMap[$id];
        }

        $playerIds = [];
        foreach ($team->getPlayers() as $player) {
            $playerIds[] = $player->getId();
        }

        $rawData = $this->fetchTeamRawAnswerData($playerIds, $query);

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
                        $overallPoints[] = [$pInfo['days'], $avg];

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
                    $teamPoints[] = [$pInfo['days'], $teamAvg];
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

    private function assertCanViewStats(int $targetUserId): void
    {
        $currentUserId = $this->authContext->getUserId();
        $currentRoles = $this->authContext->getRoles() ?? [];

        if (in_array('JUG', $currentRoles, true) && $targetUserId !== $currentUserId) {
            throw new AppException('FORBIDDEN');
        }

        if (in_array('REP', $currentRoles, true)) {
            $count = (int) $this->entityManager->createQueryBuilder()
                ->select('COUNT(rp.player)')
                ->from(\App\Features\Representatives\Domain\Entities\RepPlayer::class, 'rp')
                ->where('IDENTITY(rp.rep) = :repId AND IDENTITY(rp.player) = :playerId')
                ->setParameter('repId', $currentUserId)
                ->setParameter('playerId', $targetUserId)
                ->getQuery()
                ->getSingleScalarResult();
            if ($count === 0) {
                throw new AppException('FORBIDDEN');
            }
        }

        if (in_array('ENT', $currentRoles, true)) {
            $playerIds = $this->teamRepository->findPlayerIdsByTrainerId($currentUserId);
            if (!in_array($targetUserId, $playerIds, true)) {
                throw new AppException('FORBIDDEN');
            }
        }
    }

    private function assertCanViewTeamStats(int $teamId): void
    {
        $currentRoles = $this->authContext->getRoles() ?? [];

        if (in_array('ENT', $currentRoles, true)) {
            $teams = $this->teamRepository->findByTrainerId($this->authContext->getUserId());
            $teamIds = array_map(fn ($t) => $t->getId(), $teams);
            if (!in_array($teamId, $teamIds, true)) {
                throw new AppException('FORBIDDEN');
            }
        }
    }

    private function fetchPeriods(): array
    {
        $conn = $this->entityManager->getConnection();
        $periods = $conn->executeQuery(
            "SELECT id, name, start_date
             FROM periods
             WHERE deleted_at IS NULL
             ORDER BY start_date ASC"
        )->fetchAllAssociative();

        if ($periods === []) {
            return [];
        }

        $minStart = null;
        foreach ($periods as $p) {
            $start = new \DateTimeImmutable($p['start_date']);
            if ($minStart === null || $start < $minStart) {
                $minStart = $start;
            }
        }

        foreach ($periods as &$p) {
            $p['days'] = $this->daysBetween($minStart, new \DateTimeImmutable($p['start_date']));
        }
        unset($p);

        return $periods;
    }

    private function fetchRawAnswerData(EvaluationStatsQuery $query): array
    {
        $conn = $this->entityManager->getConnection();
        $params = ['targetUserId' => $query->targetUserId];

        $sql = "SELECT e.id AS evaluation_id, e.created_at AS evaluated_at, e.questionnaire_id, e.period_id,
                       e.observaciones, e.responder_user_id, a.question_id, q.text AS question_text, q.type AS question_type, a.value
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

    private function groupBySession(array $rawData, array $optionMap): array
    {
        $grouped = [];

        foreach ($rawData as $r) {
            $qId     = (int) $r['questionnaire_id'];
            $evalId  = (int) $r['evaluation_id'];
            $questId = (int) $r['question_id'];
            $type    = $r['question_type'];
            $value   = $r['value'];

            $numericVal = $this->resolveNumericValue($type, $value, $optionMap[$questId] ?? []);
            if ($numericVal === null) {
                continue;
            }

            if (!isset($grouped[$qId][$evalId])) {
                $grouped[$qId][$evalId] = [
                    'evaluated_at' => new \DateTimeImmutable($r['evaluated_at']),
                    'period_id'    => (int) $r['period_id'],
                    'observaciones' => $r['observaciones'] ?? null,
                    'responder_user_id' => isset($r['responder_user_id']) ? (int) $r['responder_user_id'] : null,
                    'answers'      => [],
                ];
            }

            $grouped[$qId][$evalId]['answers'][$questId] = [
                'value'         => $numericVal,
                'question_text' => $r['question_text'],
                'question_type' => $type,
            ];
        }

        return $grouped;
    }

    private function findEarliestDate(array $sessions): \DateTimeImmutable
    {
        $earliest = null;
        foreach ($sessions as $session) {
            if ($earliest === null || $session['evaluated_at'] < $earliest) {
                $earliest = $session['evaluated_at'];
            }
        }
        return $earliest;
    }

    private function daysBetween(\DateTimeImmutable $reference, \DateTimeImmutable $date): float
    {
        return round(($date->getTimestamp() - $reference->getTimestamp()) / 86400, 2);
    }

    private function attachObservations(array &$observations, ?array $regression): void
    {
        if ($regression === null) {
            return;
        }
        foreach ($observations as &$obs) {
            $obs['fitted']   = round($regression['slope'] * $obs['x_days'] + $regression['intercept'], 4);
            $obs['residual'] = round($obs['value'] - $obs['fitted'], 4);
        }
        unset($obs);
    }

    private function periodAveragesFromSessions(array $sessions, array $periodMap, int $questId): array
    {
        $byPeriod = [];
        foreach ($sessions as $session) {
            if (!isset($session['answers'][$questId])) {
                continue;
            }
            $byPeriod[$session['period_id']][] = $session['answers'][$questId]['value'];
        }

        $result = [];
        foreach ($periodMap as $pId => $pInfo) {
            if (isset($byPeriod[$pId]) && $byPeriod[$pId] !== []) {
                $result[] = [
                    'period_id'   => $pId,
                    'period_name' => $pInfo['name'],
                    'index'       => $pInfo['index'],
                    'average'     => round(array_sum($byPeriod[$pId]) / count($byPeriod[$pId]), 2),
                    'count'       => count($byPeriod[$pId]),
                ];
            }
        }
        return $result;
    }

    private function regressionFromAverages(array $averages, array $periodMap): ?array
    {
        $points = [];
        foreach ($averages as $a) {
            $points[] = [$periodMap[$a['period_id']]['index'], $a['average']];
        }
        return $this->computeRegression($points);
    }

    private function overallPeriodAveragesFromSessions(array $sessions, array $periodMap): array
    {
        $byPeriod = [];
        foreach ($sessions as $session) {
            $values = array_column($session['answers'], 'value');
            if ($values === []) {
                continue;
            }
            $byPeriod[$session['period_id']][] = array_sum($values) / count($values);
        }

        $result = [];
        foreach ($periodMap as $pId => $pInfo) {
            if (isset($byPeriod[$pId]) && $byPeriod[$pId] !== []) {
                $result[] = [
                    'period_id'   => $pId,
                    'period_name' => $pInfo['name'],
                    'index'       => $pInfo['index'],
                    'average'     => round(array_sum($byPeriod[$pId]) / count($byPeriod[$pId]), 2),
                    'count'       => count($byPeriod[$pId]),
                ];
            }
        }
        return $result;
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
        $standardErrors = $regression->standardErrors();

        $trend = match (true) {
            $slope > 0.05  => 'upward',
            $slope < -0.05 => 'downward',
            default        => 'stable',
        };

        return [
            'slope'               => round($slope, 4),
            'intercept'           => round($intercept, 4),
            'r_squared'           => round($rSquared, 4),
            'trend'               => $trend,
            'sse'                 => round($regression->sumOfSquaresResidual(), 4),
            'std_error_slope'     => round($standardErrors['m'], 4),
            'std_error_intercept' => round($standardErrors['b'], 4),
            'n'                   => count($points),
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
