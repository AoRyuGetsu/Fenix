<?php

declare(strict_types=1);

namespace App\Features\People\Application\Controllers;

use App\Core\Env;
use App\Core\Interfaces\ControllerInterface;
use App\Features\People\Application\DTO\PeopleTypeQuery;
use App\Features\People\Domain\Interfaces\PeopleTypeServiceInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PeopleTypeController implements ControllerInterface
{
    public function __construct(
        private PeopleTypeServiceInterface $peopleTypeService,
        private Request $request,
    ) {}

    public function execute(): Response
    {
        return new JsonResponse(['error' => getError('NOT_FOUND'), 'code' => 'NOT_FOUND'], 404);
    }

    #[OA\Get(path: '/people-types', tags: ['People'], summary: 'Listar tipos de persona paginados')]
    #[OA\Parameter(name: 'page', in: 'query', description: 'Número de página', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'perPage', in: 'query', description: 'Elementos por página', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'code', in: 'query', description: 'Filtrar por código', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'description', in: 'query', description: 'Filtrar por descripción', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Lista paginada de tipos de persona', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    public function list(): Response
    {
        $page = (int) ($this->request->query->get('page', '1'));
        $perPage = (int) ($this->request->query->get('perPage', Env::get('PAGINATION_PER_PAGE', '20')));

        $query = new PeopleTypeQuery(
            code: $this->request->query->get('code'),
            description: $this->request->query->get('description'),
            page: max(1, $page),
            perPage: max(1, min(100, $perPage)),
        );

        [$types, $total] = $this->peopleTypeService->list($query);

        $data = array_map(fn ($t) => [
            'id'          => $t->getId(),
            'code'        => $t->getCode(),
            'description' => $t->getDescription(),
        ], $types);

        return new JsonResponse([
            'data'    => $data,
            'total'   => $total,
            'page'    => $query->page,
            'perPage' => $query->perPage,
        ]);
    }
}
