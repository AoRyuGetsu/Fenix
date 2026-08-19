<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Application\Controllers;

use App\Core\Env;
use App\Core\Interfaces\ControllerInterface;
use App\Features\Questionnaire\Application\DTO\EvaluationQuery;
use App\Features\Questionnaire\Application\DTO\QuestionnaireQuery;
use App\Features\Questionnaire\Domain\Interfaces\EvaluationServiceInterface;
use App\Features\Periods\Domain\Interfaces\PeriodRepositoryInterface;
use App\Features\Questionnaire\Domain\Interfaces\QuestionnaireServiceInterface;
use App\Features\Representatives\Domain\Interfaces\RepPlayerServiceInterface;
use App\Features\Teams\Domain\Interfaces\TeamRepositoryInterface;
use App\Features\User\Domain\Interfaces\UserRepositoryInterface;
use App\Infrastructure\Auth\AuthContext;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EvaluationController implements ControllerInterface
{
    public function __construct(
        private EvaluationServiceInterface $evaluationService,
        private QuestionnaireServiceInterface $questionnaireService,
        private PeriodRepositoryInterface $periodRepository,
        private UserRepositoryInterface $userRepository,
        private TeamRepositoryInterface $teamRepository,
        private RepPlayerServiceInterface $representativeService,
        private AuthContext $authContext,
        private Request $request,
    ) {}

    public function execute(): Response
    {
        return new JsonResponse(['error' => getError('NOT_FOUND'), 'code' => 'NOT_FOUND'], 404);
    }

    #[OA\Get(path: '/evaluations/my-questionnaires', tags: ['Evaluation'], summary: 'Listar cuestionarios disponibles según roles del usuario autenticado')]
    #[OA\Parameter(name: 'title', in: 'query', description: 'Filtrar por título', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'active', in: 'query', description: 'Filtrar por estado activo', required: false, schema: new OA\Schema(type: 'boolean'))]
    #[OA\Parameter(name: 'page', in: 'query', description: 'Número de página', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'perPage', in: 'query', description: 'Elementos por página', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Lista paginada de cuestionarios', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    public function myQuestionnaires(): Response
    {
        $page = (int) ($this->request->query->get('page', '1'));
        $perPage = (int) ($this->request->query->get('perPage', Env::get('PAGINATION_PER_PAGE', '20')));

        $activeRaw = $this->request->query->get('active');
        $active = $activeRaw !== null ? filter_var($activeRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;

        $query = new QuestionnaireQuery(
            title: $this->request->query->get('title'),
            active: $active,
            page: max(1, $page),
            perPage: max(1, min(100, $perPage)),
        );

        $roleNames = $this->authContext->getRoles();
        [$items, $total] = $this->questionnaireService->findByResponderRoles($roleNames, $query);

        $data = array_map(fn ($q) => [
            'id'               => $q->getId(),
            'title'            => $q->getTitle(),
            'description'      => $q->getDescription(),
            'active'           => $q->isActive(),
            'responder_role_id'=> $q->getResponderRole()->getId(),
            'responder_role'   => $q->getResponderRole()->getCode(),
            'target_role_id'   => $q->getTargetRole()->getId(),
            'target_role'      => $q->getTargetRole()->getCode(),
            'questions_count'  => array_sum(
                array_map(fn ($s) => $s->getQuestions()->count(), $q->getSections()->toArray())
            ),
            'created_at'       => $q->getCreatedAt()->format('c'),
            'updated_at'       => $q->getUpdatedAt()->format('c'),
        ], $items);

        return new JsonResponse([
            'data'    => $data,
            'total'   => $total,
            'page'    => $query->page,
            'perPage' => $query->perPage,
        ]);
    }

    #[OA\Post(path: '/evaluations', tags: ['Evaluation'], summary: 'Crear evaluación con respuestas')]
    #[OA\RequestBody(content: new OA\JsonContent(properties: [
        new OA\Property(property: 'questionnaire_id', type: 'integer'),
        new OA\Property(property: 'target_user_id', type: 'integer'),
        new OA\Property(property: 'period_id', type: 'integer'),
        new OA\Property(property: 'answers', type: 'array', items: new OA\Items(
            properties: [
                new OA\Property(property: 'question_id', type: 'integer'),
                new OA\Property(property: 'value', type: 'string'),
            ],
            type: 'object'
        )),
    ]))]
    #[OA\Response(response: 201, description: 'Evaluación creada')]
    #[OA\Response(response: 422, description: 'Datos inválidos')]
    public function create(): Response
    {
        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => getError('VALIDATION_JSON_INVALID'), 'code' => 'VALIDATION_JSON_INVALID'], 400);
        }

        $required = ['questionnaire_id', 'target_user_id', 'period_id', 'answers'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return new JsonResponse(['error' => getError('VALIDATION_REQUIRED'), 'code' => 'VALIDATION_REQUIRED'], 422);
            }
        }

        if (!is_array($data['answers']) || $data['answers'] === []) {
            return new JsonResponse(['error' => getError('VALIDATION_ARRAY_REQUIRED'), 'code' => 'VALIDATION_ARRAY_REQUIRED'], 422);
        }

        try {
            $evaluation = $this->evaluationService->create(
                (int) $data['questionnaire_id'],
                (int) $data['target_user_id'],
                (int) $data['period_id'],
                $data['answers'],
            );
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse($this->serialize($evaluation), 201);
    }

    #[OA\Put(path: '/evaluations/{id}', tags: ['Evaluation'], summary: 'Actualizar respuestas de una evaluación')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(content: new OA\JsonContent(properties: [
        new OA\Property(property: 'answers', type: 'array', items: new OA\Items(
            properties: [
                new OA\Property(property: 'question_id', type: 'integer'),
                new OA\Property(property: 'value', type: 'string'),
            ],
            type: 'object'
        )),
    ]))]
    #[OA\Response(response: 200, description: 'Evaluación actualizada')]
    #[OA\Response(response: 404, description: 'No encontrado')]
    public function update(int $id): Response
    {
        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => getError('VALIDATION_JSON_INVALID'), 'code' => 'VALIDATION_JSON_INVALID'], 400);
        }

        if (!isset($data['answers']) || !is_array($data['answers']) || $data['answers'] === []) {
            return new JsonResponse(['error' => getError('VALIDATION_ARRAY_REQUIRED'), 'code' => 'VALIDATION_ARRAY_REQUIRED'], 422);
        }

        try {
            $evaluation = $this->evaluationService->update($id, $data['answers']);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse($this->serialize($evaluation));
    }

    #[OA\Get(path: '/evaluations', tags: ['Evaluation'], summary: 'Listar evaluaciones del usuario autenticado')]
    #[OA\Parameter(name: 'questionnaire_id', in: 'query', description: 'Filtrar por cuestionario', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'target_user_id', in: 'query', description: 'Filtrar por usuario evaluado', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'page', in: 'query', description: 'Número de página', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'perPage', in: 'query', description: 'Elementos por página', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Lista paginada', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    public function list(): Response
    {
        $page = (int) ($this->request->query->get('page', '1'));
        $perPage = (int) ($this->request->query->get('perPage', Env::get('PAGINATION_PER_PAGE', '20')));

        $query = new EvaluationQuery(
            responderUserId: $this->authContext->getUserId(),
            questionnaireId: $this->request->query->get('questionnaire_id') !== null ? (int) $this->request->query->get('questionnaire_id') : null,
            targetUserId: $this->request->query->get('target_user_id') !== null ? (int) $this->request->query->get('target_user_id') : null,
            page: max(1, $page),
            perPage: max(1, min(100, $perPage)),
        );

        [$items, $total] = $this->evaluationService->list($query);

        $data = array_map(fn ($e) => $this->serializeList($e), $items);

        return new JsonResponse([
            'data'    => $data,
            'total'   => $total,
            'page'    => $query->page,
            'perPage' => $query->perPage,
        ]);
    }

    #[OA\Get(path: '/evaluations/{id}', tags: ['Evaluation'], summary: 'Obtener evaluación por ID con respuestas')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Evaluación encontrada')]
    #[OA\Response(response: 404, description: 'No encontrado')]
    public function show(int $id): Response
    {
        $evaluation = $this->evaluationService->findById($id);

        if ($evaluation === null) {
            return new JsonResponse(['error' => getError('EVALUATION_NOT_FOUND'), 'code' => 'EVALUATION_NOT_FOUND'], 404);
        }

        return new JsonResponse($this->serialize($evaluation));
    }

    #[OA\Delete(path: '/evaluations/{id}', tags: ['Evaluation'], summary: 'Eliminar evaluación (soft delete)')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 204, description: 'Eliminado')]
    #[OA\Response(response: 404, description: 'No encontrado')]
    public function delete(int $id): Response
    {
        try {
            $this->evaluationService->softDelete($id);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse(null, 204);
    }

    private function getUserName($user): string
    {
        $person = $user->getPerson();
        if ($person !== null) {
            return trim(implode(' ', array_filter([
                $person->getFirstName(),
                $person->getMiddleName(),
                $person->getFirstSurname(),
                $person->getSecondSurname(),
            ]))) ?: $user->getEmail();
        }
        return $user->getEmail();
    }

    public function listPeriods(): Response
    {
        $periods = $this->periodRepository->findAll();
        $data = array_map(fn ($p) => [
            'id'         => $p->getId(),
            'name'       => $p->getName(),
            'start_date' => $p->getStartDate()->format('Y-m-d'),
            'end_date'   => $p->getEndDate()->format('Y-m-d'),
        ], $periods);

        return new JsonResponse($data);
    }

    #[OA\Get(path: '/evaluations/period-targets', tags: ['Evaluation'], summary: 'Listar usuarios evaluados y no evaluados para un periodo y cuestionario')]
    #[OA\Parameter(name: 'period_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'questionnaire_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Usuarios agrupados')]
    public function periodTargets(): Response
    {
        $periodId = (int) ($this->request->query->get('period_id', '0'));
        $questionnaireId = (int) ($this->request->query->get('questionnaire_id', '0'));
        $page = max(1, (int) ($this->request->query->get('page', '1')));
        $perPage = max(1, min(100, (int) ($this->request->query->get('perPage', Env::get('PAGINATION_PER_PAGE', '20')))));

        if ($periodId <= 0 || $questionnaireId <= 0) {
            return new JsonResponse(['error' => getError('VALIDATION_PERIOD_QUES_REQ'), 'code' => 'VALIDATION_PERIOD_QUES_REQ'], 422);
        }

        $period = $this->periodRepository->findById($periodId);
        if ($period === null) {
            return new JsonResponse(['error' => getError('PERIOD_NOT_FOUND'), 'code' => 'PERIOD_NOT_FOUND'], 404);
        }

        $questionnaire = $this->questionnaireService->findById($questionnaireId);
        if ($questionnaire === null) {
            return new JsonResponse(['error' => getError('QUESTIONNAIRE_NOT_FOUND'), 'code' => 'QUESTIONNAIRE_NOT_FOUND'], 404);
        }

        $targetRoleCode = $questionnaire->getTargetRole()->getCode();

        $targetPlayerIds = null;
        $responderRoles = $this->authContext->getRoles();
        if ($targetRoleCode === 'JUG' && in_array('ENT', $responderRoles, true)) {
            $playerIds = $this->teamRepository->findPlayerIdsByTrainerId($this->authContext->getUserId());
            $targetPlayerIds = $playerIds !== [] ? $playerIds : null;
        }

        $usersResult = $this->userRepository->findPaginated(
            new \App\Features\User\Application\DTO\UserQuery(
                role: $targetRoleCode,
                playerIds: $targetPlayerIds,
                page: $page,
                perPage: $perPage,
            )
        );
        [$allTargetUsers, $total] = $usersResult;

        $evaluationsResult = $this->evaluationService->list(new EvaluationQuery(
            responderUserId: $this->authContext->getUserId(),
            questionnaireId: $questionnaireId,
            periodId: $periodId,
            page: 1,
            perPage: 1000,
        ));
        $existingEvaluations = $evaluationsResult[0];

        $evaluatedUserIds = [];
        $evaluationMap = [];
        foreach ($existingEvaluations as $ev) {
            $uid = $ev->getTargetUser()->getId();
            $evaluatedUserIds[$uid] = true;
            $evaluationMap[$uid] = [
                'evaluation_id' => $ev->getId(),
                'user' => $this->serializeUserItem($ev->getTargetUser()),
                'answers' => $this->serializeAnswers($ev),
            ];
        }

        $unevaluated = [];
        $evaluated = [];
        foreach ($allTargetUsers as $user) {
            $uid = $user->getId();
            $item = $this->serializeUserItem($user);
            if (isset($evaluatedUserIds[$uid])) {
                $evaluated[] = $evaluationMap[$uid];
            } else {
                $unevaluated[] = $item;
            }
        }

        return new JsonResponse([
            'data' => [
                'unevaluated' => $unevaluated,
                'evaluated'   => $evaluated,
            ],
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
        ]);
    }

    private function serializeUserItem($user): array
    {
        $person = null;
        $userPerson = $user->getPerson();
        if ($userPerson !== null) {
            $person = [
                'id'              => $userPerson->getId(),
                'type'            => $userPerson->getPeopleType()->getCode(),
                'first_name'      => $userPerson->getFirstName(),
                'middle_name'     => $userPerson->getMiddleName(),
                'first_surname'   => $userPerson->getFirstSurname(),
                'second_surname'  => $userPerson->getSecondSurname(),
                'document_number' => $userPerson->getDocumentNumber(),
                'email'           => $userPerson->getEmail(),
                'phone'           => $userPerson->getPhone(),
                'address'         => $userPerson->getAddress(),
            ];
        }

        $roles = $user->getRoles()->map(fn($r) => $r->getCode())->toArray();
        $rep = $this->representativeService->getRepresentative((int) $user->getId());

        return [
            'id'            => $user->getId(),
            'email'         => $user->getEmail(),
            'active'        => $user->isActive(),
            'roles'         => $roles,
            'person'        => $person,
            'representative' => $rep !== null ? [
                'id'           => $rep->getId(),
                'display_name' => $this->getUserName($rep),
                'email'        => $rep->getEmail(),
            ] : null,
            'created_at'    => $user->getCreatedAt()->format('c'),
            'updated_at'    => $user->getUpdatedAt()->format('c'),
            'display_name'  => $this->getUserName($user),
        ];
    }

    private function serializeAnswers($e): array
    {
        $answers = [];
        foreach ($e->getAnswers() as $answer) {
            if ($answer->getDeletedAt() !== null) {
                continue;
            }
            $answers[] = [
                'id'           => $answer->getId(),
                'question_id'  => $answer->getQuestion()->getId(),
                'question_text'=> $answer->getQuestion()->getText(),
                'question_type'=> $answer->getQuestion()->getType(),
                'value'        => $answer->getValue(),
            ];
        }
        return $answers;
    }

    private function serializeList($e): array
    {
        return [
            'id'                => $e->getId(),
            'questionnaire_id'  => $e->getQuestionnaire()->getId(),
            'questionnaire_title' => $e->getQuestionnaire()->getTitle(),
            'period_id'         => $e->getPeriod()->getId(),
            'period_name'       => $e->getPeriod()->getName(),
            'responder_user_id' => $e->getResponderUser()->getId(),
            'responder_email'   => $e->getResponderUser()->getEmail(),
            'target_user_id'    => $e->getTargetUser()->getId(),
            'target_email'      => $e->getTargetUser()->getEmail(),
            'answers_count'     => $e->getAnswers()->count(),
            'created_at'        => $e->getCreatedAt()->format('c'),
        ];
    }

    private function serialize($e): array
    {
        return [
            'id'                => $e->getId(),
            'questionnaire_id'  => $e->getQuestionnaire()->getId(),
            'questionnaire_title' => $e->getQuestionnaire()->getTitle(),
            'period_id'         => $e->getPeriod()->getId(),
            'period_name'       => $e->getPeriod()->getName(),
            'responder_user_id' => $e->getResponderUser()->getId(),
            'responder_email'   => $e->getResponderUser()->getEmail(),
            'target_user_id'    => $e->getTargetUser()->getId(),
            'target_email'      => $this->getUserName($e->getTargetUser()),
            'answers'           => $this->serializeAnswers($e),
            'created_at'        => $e->getCreatedAt()->format('c'),
            'updated_at'        => $e->getUpdatedAt()->format('c'),
        ];
    }
}
