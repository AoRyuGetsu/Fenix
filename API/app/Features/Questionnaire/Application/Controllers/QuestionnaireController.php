<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Application\Controllers;

use App\Core\Env;
use App\Core\Interfaces\ControllerInterface;
use App\Features\Questionnaire\Application\DTO\QuestionnaireQuery;
use App\Features\Questionnaire\Domain\Interfaces\QuestionnaireServiceInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class QuestionnaireController implements ControllerInterface
{
    public function __construct(
        private QuestionnaireServiceInterface $questionnaireService,
        private Request $request,
    ) {}

    public function execute(): Response
    {
        return new JsonResponse(['error' => getError('NOT_FOUND'), 'code' => 'NOT_FOUND'], 404);
    }

    #[OA\Get(path: '/questionnaires', tags: ['Questionnaire'], summary: 'Listar cuestionarios paginados')]
    #[OA\Parameter(name: 'title', in: 'query', description: 'Filtrar por título', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'active', in: 'query', description: 'Filtrar por estado activo', required: false, schema: new OA\Schema(type: 'boolean'))]
    #[OA\Parameter(name: 'responder_role_id', in: 'query', description: 'Filtrar por rol que responde', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'target_role_id', in: 'query', description: 'Filtrar por rol evaluado', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'page', in: 'query', description: 'Número de página', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'perPage', in: 'query', description: 'Elementos por página', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Lista paginada', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    public function list(): Response
    {
        $page = (int) ($this->request->query->get('page', '1'));
        $perPage = (int) ($this->request->query->get('perPage', Env::get('PAGINATION_PER_PAGE', '20')));

        $activeRaw = $this->request->query->get('active');
        $active = $activeRaw !== null ? filter_var($activeRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;

        $query = new QuestionnaireQuery(
            title: $this->request->query->get('title'),
            active: $active,
            responderRoleId: $this->request->query->get('responder_role_id') !== null ? (int) $this->request->query->get('responder_role_id') : null,
            targetRoleId: $this->request->query->get('target_role_id') !== null ? (int) $this->request->query->get('target_role_id') : null,
            page: max(1, $page),
            perPage: max(1, min(100, $perPage)),
        );

        [$items, $total] = $this->questionnaireService->list($query);

        $data = array_map(fn ($q) => $this->serialize($q), $items);

        return new JsonResponse([
            'data'    => $data,
            'total'   => $total,
            'page'    => $query->page,
            'perPage' => $query->perPage,
        ]);
    }

    #[OA\Post(path: '/questionnaires', tags: ['Questionnaire'], summary: 'Crear cuestionario')]
    #[OA\RequestBody(content: new OA\JsonContent(properties: [
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'responder_role_id', type: 'integer'),
        new OA\Property(property: 'target_role_id', type: 'integer'),
    ]))]
    #[OA\Response(response: 201, description: 'Cuestionario creado')]
    #[OA\Response(response: 422, description: 'Datos inválidos')]
    public function create(): Response
    {
        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => getError('VALIDATION_JSON_INVALID'), 'code' => 'VALIDATION_JSON_INVALID'], 400);
        }

        $required = ['title', 'responder_role_id', 'target_role_id'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return new JsonResponse(['error' => getError('VALIDATION_REQUIRED'), 'code' => 'VALIDATION_REQUIRED'], 422);
            }
        }

        try {
            $questionnaire = $this->questionnaireService->create($data);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse($this->serialize($questionnaire), 201);
    }

    #[OA\Get(path: '/questionnaires/{id}', tags: ['Questionnaire'], summary: 'Obtener cuestionario por ID')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Cuestionario encontrado')]
    #[OA\Response(response: 404, description: 'No encontrado')]
    public function show(int $id): Response
    {
        $questionnaire = $this->questionnaireService->findById($id);

        if ($questionnaire === null) {
            return new JsonResponse(['error' => getError('QUESTIONNAIRE_NOT_FOUND'), 'code' => 'QUESTIONNAIRE_NOT_FOUND'], 404);
        }

        return new JsonResponse($this->serialize($questionnaire));
    }

    #[OA\Put(path: '/questionnaires/{id}', tags: ['Questionnaire'], summary: 'Actualizar cuestionario')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(content: new OA\JsonContent(properties: [
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'active', type: 'boolean'),
        new OA\Property(property: 'responder_role_id', type: 'integer'),
        new OA\Property(property: 'target_role_id', type: 'integer'),
    ]))]
    #[OA\Response(response: 200, description: 'Cuestionario actualizado')]
    #[OA\Response(response: 404, description: 'No encontrado')]
    public function update(int $id): Response
    {
        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => getError('VALIDATION_JSON_INVALID'), 'code' => 'VALIDATION_JSON_INVALID'], 400);
        }

        try {
            $questionnaire = $this->questionnaireService->update($id, $data);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse($this->serialize($questionnaire));
    }

    #[OA\Delete(path: '/questionnaires/{id}', tags: ['Questionnaire'], summary: 'Eliminar cuestionario (soft delete)')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 204, description: 'Eliminado')]
    #[OA\Response(response: 404, description: 'No encontrado')]
    public function delete(int $id): Response
    {
        try {
            $this->questionnaireService->softDelete($id);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse(null, 204);
    }

    private function serialize($q): array
    {
        $sections = [];
        $totalQuestions = 0;

        foreach ($q->getSections() as $section) {
            if ($section->getDeletedAt() !== null) {
                continue;
            }

            $questions = [];
            foreach ($section->getQuestions() as $question) {
                if ($question->getDeletedAt() !== null) {
                    continue;
                }
                $qData = [
                    'id'             => $question->getId(),
                    'text'           => $question->getText(),
                    'type'           => $question->getType(),
                    'question_order' => $question->getQuestionOrder(),
                ];
                if ($question->getType() === 'selection') {
                    $qData['options'] = array_map(fn ($o) => [
                        'id'           => $o->getId(),
                        'option_text'  => $o->getOptionText(),
                        'option_order' => $o->getOptionOrder(),
                    ], $question->getOptions()->toArray());
                }
                $questions[] = $qData;
            }

            $sections[] = [
                'id'              => $section->getId(),
                'title'           => $section->getTitle(),
                'description'     => $section->getDescription(),
                'section_order'   => $section->getSectionOrder(),
                'questions'       => $questions,
                'questions_count' => count($questions),
            ];

            $totalQuestions += count($questions);
        }

        return [
            'id'               => $q->getId(),
            'title'            => $q->getTitle(),
            'description'      => $q->getDescription(),
            'active'           => $q->isActive(),
            'responder_role_id'=> $q->getResponderRole()->getId(),
            'responder_role'   => $q->getResponderRole()->getCode(),
            'target_role_id'   => $q->getTargetRole()->getId(),
            'target_role'      => $q->getTargetRole()->getCode(),
            'sections'         => $sections,
            'questions_count'  => $totalQuestions,
            'created_at'       => $q->getCreatedAt()->format('c'),
            'updated_at'       => $q->getUpdatedAt()->format('c'),
        ];
    }
}
