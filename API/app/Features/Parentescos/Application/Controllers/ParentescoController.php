<?php

declare(strict_types=1);

namespace App\Features\Parentescos\Application\Controllers;

use App\Core\Env;
use App\Core\Interfaces\ControllerInterface;
use App\Features\Parentescos\Application\DTO\ParentescoQuery;
use App\Features\Parentescos\Domain\Interfaces\ParentescoServiceInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ParentescoController implements ControllerInterface
{
    public function __construct(
        private ParentescoServiceInterface $parentescoService,
        private Request $request,
    ) {}

    public function execute(): Response
    {
        return new JsonResponse(['error' => getError('NOT_FOUND'), 'code' => 'NOT_FOUND'], 404);
    }

    #[OA\Get(path: '/parentescos', tags: ['Parentescos'], summary: 'Listar parentescos paginados')]
    #[OA\Parameter(name: 'page', in: 'query', description: 'Número de página', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'perPage', in: 'query', description: 'Elementos por página', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'code', in: 'query', description: 'Filtrar por código', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'description', in: 'query', description: 'Filtrar por descripción', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Lista paginada de parentescos', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    public function list(): Response
    {
        $page = (int) ($this->request->query->get('page', '1'));
        $perPage = (int) ($this->request->query->get('perPage', Env::get('PAGINATION_PER_PAGE', '20')));

        $query = new ParentescoQuery(
            code: $this->request->query->get('code'),
            description: $this->request->query->get('description'),
            page: max(1, $page),
            perPage: max(1, min(100, $perPage)),
        );

        [$items, $total] = $this->parentescoService->list($query);

        $data = array_map(fn ($p) => [
            'id'          => $p->getId(),
            'code'        => $p->getCode(),
            'description' => $p->getDescription(),
        ], $items);

        return new JsonResponse([
            'data'    => $data,
            'total'   => $total,
            'page'    => $query->page,
            'perPage' => $query->perPage,
        ]);
    }
}
