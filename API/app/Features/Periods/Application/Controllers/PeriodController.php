<?php

declare(strict_types=1);

namespace App\Features\Periods\Application\Controllers;

use App\Core\Env;
use App\Core\Interfaces\ControllerInterface;
use App\Features\Periods\Application\DTO\PeriodQuery;
use App\Features\Periods\Domain\Interfaces\PeriodServiceInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PeriodController implements ControllerInterface
{
    public function __construct(
        private PeriodServiceInterface $periodService,
        private Request $request,
    ) {}

    public function execute(): Response
    {
        return new JsonResponse(['error' => 'Not found'], 404);
    }

    #[OA\Get(path: '/periods', tags: ['Periods'], summary: 'Listar periodos activos')]
    #[OA\Parameter(name: 'name', in: 'query', description: 'Filter by name', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'perPage', in: 'query', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Lista paginada de periodos', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    public function list(): Response
    {
        $page = (int) ($this->request->query->get('page', '1'));
        $perPage = (int) ($this->request->query->get('perPage', Env::get('PAGINATION_PER_PAGE', '20')));

        $query = new PeriodQuery(
            name: $this->request->query->get('name'),
            page: max(1, $page),
            perPage: max(1, min(100, $perPage)),
        );

        [$items, $total] = $this->periodService->findPaginated($query);

        $data = array_map(fn ($p) => [
            'id'         => $p->getId(),
            'name'       => $p->getName(),
            'start_date' => $p->getStartDate()->format('Y-m-d'),
            'end_date'   => $p->getEndDate()->format('Y-m-d'),
        ], $items);

        return new JsonResponse([
            'data'    => $data,
            'total'   => $total,
            'page'    => $query->page,
            'perPage' => $query->perPage,
        ]);
    }

    #[OA\Get(path: '/periods/{id}', tags: ['Periods'], summary: 'Obtener periodo por ID')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Periodo encontrado', content: new OA\JsonContent(ref: '#/components/schemas/Period'))]
    #[OA\Response(response: 404, description: 'No encontrado')]
    public function show(int $id): Response
    {
        $period = $this->periodService->findById($id);

        if ($period === null) {
            return new JsonResponse(['error' => 'Period not found'], 404);
        }

        return new JsonResponse([
            'id'         => $period->getId(),
            'name'       => $period->getName(),
            'start_date' => $period->getStartDate()->format('Y-m-d'),
            'end_date'   => $period->getEndDate()->format('Y-m-d'),
        ]);
    }

    #[OA\Post(path: '/periods', tags: ['Periods'], summary: 'Crear periodo')]
    #[OA\RequestBody(content: new OA\JsonContent(properties: [
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'start_date', type: 'string', format: 'date'),
        new OA\Property(property: 'end_date', type: 'string', format: 'date'),
    ]))]
    #[OA\Response(response: 201, description: 'Periodo creado')]
    #[OA\Response(response: 422, description: 'Datos inválidos')]
    public function create(): Response
    {
        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $required = ['name', 'start_date', 'end_date'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                return new JsonResponse(['error' => "$field is required"], 422);
            }
        }

        try {
            $period = $this->periodService->create(
                trim($data['name']),
                $data['start_date'],
                $data['end_date'],
            );
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse([
            'id'         => $period->getId(),
            'name'       => $period->getName(),
            'start_date' => $period->getStartDate()->format('Y-m-d'),
            'end_date'   => $period->getEndDate()->format('Y-m-d'),
        ], 201);
    }

    #[OA\Put(path: '/periods/{id}', tags: ['Periods'], summary: 'Actualizar periodo')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(content: new OA\JsonContent(properties: [
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'start_date', type: 'string', format: 'date'),
        new OA\Property(property: 'end_date', type: 'string', format: 'date'),
    ]))]
    #[OA\Response(response: 200, description: 'Periodo actualizado')]
    #[OA\Response(response: 404, description: 'No encontrado')]
    #[OA\Response(response: 422, description: 'Datos inválidos')]
    public function update(int $id): Response
    {
        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $required = ['name', 'start_date', 'end_date'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                return new JsonResponse(['error' => "$field is required"], 422);
            }
        }

        try {
            $period = $this->periodService->update(
                $id,
                trim($data['name']),
                $data['start_date'],
                $data['end_date'],
            );
        } catch (\App\Core\AppException $e) {
            if ($e->getMessage() === 'Periodo no encontrado') {
                return new JsonResponse(['error' => $e->getMessage()], 404);
            }
            return new JsonResponse(['error' => $e->getMessage()], 409);
        }

        return new JsonResponse([
            'id'         => $period->getId(),
            'name'       => $period->getName(),
            'start_date' => $period->getStartDate()->format('Y-m-d'),
            'end_date'   => $period->getEndDate()->format('Y-m-d'),
        ]);
    }

    #[OA\Delete(path: '/periods/{id}', tags: ['Periods'], summary: 'Eliminar periodo (soft delete)')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 204, description: 'Eliminado')]
    #[OA\Response(response: 404, description: 'No encontrado')]
    public function delete(int $id): Response
    {
        try {
            $this->periodService->softDelete($id);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse(null, 204);
    }
}
