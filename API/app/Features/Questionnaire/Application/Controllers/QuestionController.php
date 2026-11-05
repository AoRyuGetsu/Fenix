<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Application\Controllers;

use App\Core\Env;
use App\Core\Interfaces\ControllerInterface;
use App\Features\Questionnaire\Application\DTO\QuestionQuery;
use App\Features\Questionnaire\Domain\Interfaces\QuestionServiceInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class QuestionController implements ControllerInterface
{
    public function __construct(
        private QuestionServiceInterface $questionService,
        private Request $request,
    ) {}

    public function execute(): Response
    {
        return new JsonResponse(['error' => getError('NOT_FOUND'), 'code' => 'NOT_FOUND'], 404);
    }

    #[OA\Get(path: '/sections/{id}/questions', tags: ['Questionnaire'], summary: 'Listar preguntas de una sección')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'perPage', in: 'query', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Lista paginada de preguntas', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    public function list(int $sectionId): Response
    {
        $page = max(1, (int) ($this->request->query->get('page', '1')));
        $perPage = max(1, min(100, (int) ($this->request->query->get('perPage', Env::get('PAGINATION_PER_PAGE', '20')))));

        $query = new QuestionQuery(
            sectionId: $sectionId,
            page: $page,
            perPage: $perPage,
        );

        [$items, $total] = $this->questionService->findPaginated($query);

        $data = array_map(fn ($q) => $this->serialize($q), $items);

        return new JsonResponse([
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
        ]);
    }

    #[OA\Post(path: '/sections/{id}/questions', tags: ['Questionnaire'], summary: 'Crear pregunta en una sección')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(content: new OA\JsonContent(properties: [
        new OA\Property(property: 'text', type: 'string'),
        new OA\Property(property: 'type', type: 'string', default: 'selection'),
        new OA\Property(property: 'question_order', type: 'integer'),
        new OA\Property(property: 'options', type: 'array', items: new OA\Items(type: 'string'), description: 'Solo para type=selection'),
    ]))]
    #[OA\Response(response: 201, description: 'Pregunta creada')]
    public function create(int $sectionId): Response
    {
        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => getError('VALIDATION_JSON_INVALID'), 'code' => 'VALIDATION_JSON_INVALID'], 400);
        }

        if (!isset($data['text'])) {
            return new JsonResponse(['error' => getError('VALIDATION_TEXT_REQUIRED'), 'code' => 'VALIDATION_TEXT_REQUIRED'], 422);
        }

        try {
            $question = $this->questionService->create($sectionId, $data);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse($this->serialize($question), 201);
    }

    #[OA\Put(path: '/questions/{id}', tags: ['Questionnaire'], summary: 'Actualizar pregunta')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(content: new OA\JsonContent(properties: [
        new OA\Property(property: 'text', type: 'string'),
        new OA\Property(property: 'type', type: 'string'),
        new OA\Property(property: 'question_order', type: 'integer'),
        new OA\Property(property: 'options', type: 'array', items: new OA\Items(type: 'string')),
    ]))]
    #[OA\Response(response: 200, description: 'Pregunta actualizada')]
    public function update(int $id): Response
    {
        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => getError('VALIDATION_JSON_INVALID'), 'code' => 'VALIDATION_JSON_INVALID'], 400);
        }

        try {
            $question = $this->questionService->update($id, $data);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse($this->serialize($question));
    }

    #[OA\Put(path: '/questions/{id}/move', tags: ['Questionnaire'], summary: 'Mover pregunta dentro de su sección (reordenar)')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(content: new OA\JsonContent(properties: [
        new OA\Property(property: 'direction', type: 'string', enum: ['up', 'down']),
    ]))]
    #[OA\Response(response: 204, description: 'Movida')]
    public function move(int $id): Response
    {
        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data) || !isset($data['direction']) || !in_array($data['direction'], ['up', 'down'], true)) {
            return new JsonResponse(['error' => getError('INVALID_ORDER_DIRECTION'), 'code' => 'INVALID_ORDER_DIRECTION'], 422);
        }

        try {
            $this->questionService->move($id, $data['direction']);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse(null, 204);
    }

    #[OA\Delete(path: '/questions/{id}', tags: ['Questionnaire'], summary: 'Eliminar pregunta (soft delete)')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 204, description: 'Eliminada')]
    public function delete(int $id): Response
    {
        try {
            $this->questionService->softDelete($id);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse(null, 204);
    }

    private function serialize($q): array
    {
        $result = [
            'id'             => $q->getId(),
            'text'           => $q->getText(),
            'type'           => $q->getType(),
            'question_order' => $q->getQuestionOrder(),
        ];

        if ($q->getType() === 'selection') {
            $result['options'] = array_map(fn ($o) => [
                'id'           => $o->getId(),
                'option_text'  => $o->getOptionText(),
                'option_order' => $o->getOptionOrder(),
                'label'        => $o->getLabel(),
            ], $q->getOptions()->toArray());
        }

        return $result;
    }
}
