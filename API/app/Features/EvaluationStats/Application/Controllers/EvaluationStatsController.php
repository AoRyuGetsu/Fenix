<?php

declare(strict_types=1);

namespace App\Features\EvaluationStats\Application\Controllers;

use App\Core\Interfaces\ControllerInterface;
use App\Features\EvaluationStats\Application\DTO\EvaluationStatsQuery;
use App\Features\EvaluationStats\Application\DTO\TeamStatsQuery;
use App\Features\EvaluationStats\Domain\Interfaces\EvaluationStatsServiceInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EvaluationStatsController implements ControllerInterface
{
    public function __construct(
        private EvaluationStatsServiceInterface $evaluationStatsService,
        private Request $request,
    ) {}

    public function execute(): Response
    {
        return new JsonResponse(['error' => getError('NOT_FOUND'), 'code' => 'NOT_FOUND'], 404);
    }

    #[OA\Get(
        path: '/evaluation-stats',
        tags: ['EvaluationStats'],
        summary: 'Obtener estadísticas de evaluaciones de un jugador',
        parameters: [
            new OA\Parameter(name: 'target_user_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer'), description: 'ID del jugador evaluado'),
            new OA\Parameter(name: 'questionnaire_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer'), description: 'Filtrar por cuestionario específico'),
            new OA\Parameter(name: 'period_ids', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Filtrar por periodos (IDs separados por coma)'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Estadísticas calculadas', content: new OA\JsonContent(ref: '#/components/schemas/EvaluationStatsResponse')),
            new OA\Response(response: 422, description: 'Error de validación'),
            new OA\Response(response: 404, description: 'Recurso no encontrado'),
            new OA\Response(response: 403, description: 'Acceso denegado'),
        ]
    )]
    public function stats(): Response
    {
        $targetUserId = $this->request->query->get('target_user_id');
        if ($targetUserId === null || $targetUserId === '') {
            return new JsonResponse(
                ['error' => getError('EVALUATION_STATS_TARGET_REQUIRED'), 'code' => 'EVALUATION_STATS_TARGET_REQUIRED'],
                422
            );
        }

        $questionnaireId = $this->request->query->get('questionnaire_id');
        $periodIdsRaw = $this->request->query->get('period_ids');
        $periodIds = null;
        if ($periodIdsRaw !== null && $periodIdsRaw !== '') {
            $parts = array_filter(explode(',', $periodIdsRaw), 'is_numeric');
            if ($parts !== []) {
                $periodIds = array_map('intval', $parts);
            }
        }

        $query = new EvaluationStatsQuery(
            targetUserId: (int) $targetUserId,
            questionnaireId: $questionnaireId !== null && $questionnaireId !== '' ? (int) $questionnaireId : null,
            periodIds: $periodIds,
        );

        try {
            $result = $this->evaluationStatsService->getStats($query);
            return new JsonResponse($result);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(
                ['error' => $e->getMessage(), 'code' => $e->getErrorCode()],
                $e->getCode(),
            );
        }
    }

    #[OA\Get(
        path: '/evaluation-stats/team/{teamId}',
        tags: ['EvaluationStats'],
        summary: 'Obtener estadísticas agregadas de un equipo',
        parameters: [
            new OA\Parameter(name: 'teamId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'ID del equipo'),
            new OA\Parameter(name: 'questionnaire_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer'), description: 'Filtrar por cuestionario específico'),
            new OA\Parameter(name: 'period_ids', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Filtrar por periodos (IDs separados por coma)'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Estadísticas del equipo', content: new OA\JsonContent(ref: '#/components/schemas/TeamStatsResponse')),
            new OA\Response(response: 404, description: 'Equipo no encontrado'),
            new OA\Response(response: 422, description: 'Sin datos'),
        ]
    )]
    public function teamStats(int $teamId): Response
    {
        $questionnaireId = $this->request->query->get('questionnaire_id');
        $periodIdsRaw = $this->request->query->get('period_ids');
        $periodIds = null;
        if ($periodIdsRaw !== null && $periodIdsRaw !== '') {
            $parts = array_filter(explode(',', $periodIdsRaw), 'is_numeric');
            if ($parts !== []) {
                $periodIds = array_map('intval', $parts);
            }
        }

        $query = new TeamStatsQuery(
            teamId: $teamId,
            questionnaireId: $questionnaireId !== null && $questionnaireId !== '' ? (int) $questionnaireId : null,
            periodIds: $periodIds,
        );

        try {
            $result = $this->evaluationStatsService->getTeamStats($query);
            return new JsonResponse($result);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(
                ['error' => $e->getMessage(), 'code' => $e->getErrorCode()],
                $e->getCode(),
            );
        }
    }
}
