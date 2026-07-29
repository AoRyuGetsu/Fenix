<?php

declare(strict_types=1);

namespace App\Features\RolesPermisos\Application\Controllers;

use App\Core\Env;
use App\Core\Interfaces\ControllerInterface;
use App\Features\RolesPermisos\Application\DTO\PermissionQuery;
use App\Features\RolesPermisos\Domain\Interfaces\RoleAccessControlServiceInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionController implements ControllerInterface
{
    public function __construct(
        private RoleAccessControlServiceInterface $roleAccessControl,
        private Request $request,
    ) {}

    public function execute(): Response
    {
        return match ($this->request->getMethod()) {
            'POST' => $this->create(),
            'GET'  => $this->list(),
            default => new JsonResponse(['error' => getError('METHOD_NOT_ALLOWED'), 'code' => 'METHOD_NOT_ALLOWED'], 405),
        };
    }

    #[OA\Post(path: '/permissions', tags: ['Permissions'], summary: 'Crear permiso')]
    #[OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/PermissionCreate'))]
    #[OA\Response(response: 201, description: 'Permiso creado', content: new OA\JsonContent(ref: '#/components/schemas/Permission'))]
    #[OA\Response(response: 409, description: 'Conflicto - ya existe', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function create(): Response
    {
        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => getError('VALIDATION_JSON_INVALID'), 'code' => 'VALIDATION_JSON_INVALID'], 400);
        }

        $name = $data['name'] ?? null;

        if ($name === null || !is_string($name)) {
            return new JsonResponse(['error' => getError('VALIDATION_NAME_REQUIRED'), 'code' => 'VALIDATION_NAME_REQUIRED'], 422);
        }

        try {
            $permission = $this->roleAccessControl->createPermission($name);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse([
            'id'   => $permission->getId(),
            'name' => $permission->getName(),
        ], 201);
    }

    #[OA\Get(path: '/permissions', tags: ['Permissions'], summary: 'Listar permisos paginados')]
    #[OA\Parameter(name: 'page', in: 'query', description: 'Número de página', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'perPage', in: 'query', description: 'Elementos por página', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'name', in: 'query', description: 'Filtrar por nombre', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Lista paginada de permisos', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    public function list(): Response
    {
        $page = (int) ($this->request->query->get('page', '1'));
        $perPage = (int) ($this->request->query->get('perPage', Env::get('PAGINATION_PER_PAGE', '20')));

        $query = new PermissionQuery(
            name: $this->request->query->get('name'),
            page: max(1, $page),
            perPage: max(1, min(100, $perPage)),
        );

        [$permissions, $total] = $this->roleAccessControl->listPermissions($query);

        $data = array_map(fn ($p) => [
            'id'   => $p->getId(),
            'name' => $p->getName(),
        ], $permissions);

        return new JsonResponse([
            'data'    => $data,
            'total'   => $total,
            'page'    => $query->page,
            'perPage' => $query->perPage,
        ]);
    }
}
