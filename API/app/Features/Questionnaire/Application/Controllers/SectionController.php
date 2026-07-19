<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Application\Controllers;

use App\Core\Interfaces\ControllerInterface;
use App\Features\Questionnaire\Domain\Interfaces\SectionServiceInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SectionController implements ControllerInterface
{
    public function __construct(
        private SectionServiceInterface $sectionService,
        private Request $request,
    ) {}

    public function execute(): Response
    {
        return new JsonResponse(['error' => 'Not found'], 404);
    }

    #[OA\Get(path: '/questionnaires/{id}/sections', tags: ['Questionnaire'], summary: 'Listar secciones de un cuestionario')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Lista de secciones')]
    public function list(int $questionnaireId): Response
    {
        $sections = $this->sectionService->findByQuestionnaire($questionnaireId);

        $data = array_map(fn ($s) => $this->serialize($s), $sections);

        return new JsonResponse($data);
    }

    #[OA\Post(path: '/questionnaires/{id}/sections', tags: ['Questionnaire'], summary: 'Crear sección en un cuestionario')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(content: new OA\JsonContent(properties: [
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'section_order', type: 'integer'),
    ]))]
    #[OA\Response(response: 201, description: 'Sección creada')]
    public function create(int $questionnaireId): Response
    {
        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        if (!isset($data['title'])) {
            return new JsonResponse(['error' => 'title is required'], 422);
        }

        try {
            $section = $this->sectionService->create($questionnaireId, $data);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse($this->serialize($section), 201);
    }

    #[OA\Put(path: '/sections/{id}', tags: ['Questionnaire'], summary: 'Actualizar sección')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(content: new OA\JsonContent(properties: [
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'section_order', type: 'integer'),
    ]))]
    #[OA\Response(response: 200, description: 'Sección actualizada')]
    public function update(int $id): Response
    {
        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        try {
            $section = $this->sectionService->update($id, $data);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse($this->serialize($section));
    }

    #[OA\Delete(path: '/sections/{id}', tags: ['Questionnaire'], summary: 'Eliminar sección (soft delete)')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 204, description: 'Eliminada')]
    public function delete(int $id): Response
    {
        try {
            $this->sectionService->softDelete($id);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse(null, 204);
    }

    private function serialize($s): array
    {
        $questions = [];
        foreach ($s->getQuestions() as $question) {
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

        return [
            'id'             => $s->getId(),
            'title'          => $s->getTitle(),
            'description'    => $s->getDescription(),
            'section_order'  => $s->getSectionOrder(),
            'questions'      => $questions,
            'questions_count' => count($questions),
            'created_at'     => $s->getCreatedAt()->format('c'),
            'updated_at'     => $s->getUpdatedAt()->format('c'),
        ];
    }
}
