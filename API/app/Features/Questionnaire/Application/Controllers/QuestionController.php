<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Application\Controllers;

use App\Core\Interfaces\ControllerInterface;
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
        return new JsonResponse(['error' => 'Not found'], 404);
    }

    #[OA\Get(path: '/sections/{id}/questions', tags: ['Questionnaire'], summary: 'Listar preguntas de una sección')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Lista de preguntas')]
    public function list(int $sectionId): Response
    {
        $questions = $this->questionService->listBySection($sectionId);

        $data = array_map(fn ($q) => $this->serialize($q), $questions);

        return new JsonResponse($data);
    }

    #[OA\Post(path: '/sections/{id}/questions', tags: ['Questionnaire'], summary: 'Crear pregunta en una sección')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(content: new OA\JsonContent(properties: [
        new OA\Property(property: 'text', type: 'string'),
        new OA\Property(property: 'type', type: 'string', default: 'text'),
        new OA\Property(property: 'question_order', type: 'integer'),
        new OA\Property(property: 'options', type: 'array', items: new OA\Items(type: 'string'), description: 'Solo para type=selection'),
    ]))]
    #[OA\Response(response: 201, description: 'Pregunta creada')]
    public function create(int $sectionId): Response
    {
        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        if (!isset($data['text'])) {
            return new JsonResponse(['error' => 'text is required'], 422);
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
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        try {
            $question = $this->questionService->update($id, $data);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse($this->serialize($question));
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
            ], $q->getOptions()->toArray());
        }

        return $result;
    }
}
