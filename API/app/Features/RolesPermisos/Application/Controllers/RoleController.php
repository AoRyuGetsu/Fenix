<?php

declare(strict_types=1);

namespace App\Features\RolesPermisos\Application\Controllers;

use App\Core\Env;
use App\Core\Interfaces\ControllerInterface;
use App\Features\RolesPermisos\Domain\Interfaces\RoleAccessControlServiceInterface;
use App\Features\RolesPermisos\Application\DTO\RoleQuery;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleController implements ControllerInterface
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

    #[OA\Post(path: '/roles', tags: ['Roles'], summary: 'Crear rol')]
    #[OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/RoleCreate'))]
    #[OA\Response(response: 201, description: 'Rol creado', content: new OA\JsonContent(ref: '#/components/schemas/Role'))]
    #[OA\Response(response: 409, description: 'Conflicto - ya existe', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function create(): Response
    {
        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => getError('VALIDATION_JSON_INVALID'), 'code' => 'VALIDATION_JSON_INVALID'], 400);
        }

        $code = $data['code'] ?? null;
        $name = $data['name'] ?? null;

        if ($code === null || !is_string($code) || $name === null || !is_string($name)) {
            return new JsonResponse(['error' => getError('VALIDATION_CODE_NAME_REQ'), 'code' => 'VALIDATION_CODE_NAME_REQ'], 422);
        }

        try {
            $role = $this->roleAccessControl->createRole($code, $name);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse([
            'id'   => $role->getId(),
            'code' => $role->getCode(),
            'name' => $role->getName(),
        ], 201);
    }

    #[OA\Get(path: '/roles', tags: ['Roles'], summary: 'Listar roles paginados')]
    #[OA\Parameter(name: 'page', in: 'query', description: 'Número de página', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'perPage', in: 'query', description: 'Elementos por página', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'name', in: 'query', description: 'Filtrar por nombre', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Lista paginada de roles', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    public function list(): Response
    {
        $page = (int) ($this->request->query->get('page', '1'));
        $perPage = (int) ($this->request->query->get('perPage', Env::get('PAGINATION_PER_PAGE', '20')));

        $query = new RoleQuery(
            name: $this->request->query->get('name'),
            page: max(1, $page),
            perPage: max(1, min(100, $perPage)),
        );

        [$roles, $total] = $this->roleAccessControl->listRoles($query);

        $data = array_map(fn ($role) => [
            'id'   => $role->getId(),
            'code' => $role->getCode(),
            'name' => $role->getName(),
        ], $roles);

        return new JsonResponse([
            'data'    => $data,
            'total'   => $total,
            'page'    => $query->page,
            'perPage' => $query->perPage,
        ]);
    }

    #[OA\Get(path: '/roles/permissions', tags: ['Roles'], summary: 'Listar roles con sus permisos (solo lectura)')]
    #[OA\Response(response: 200, description: 'Roles con permisos', content: new OA\JsonContent(ref: '#/components/schemas/RolesPermissionsResponse'))]
    public function listWithPermissions(): Response
    {
        $permissions = $this->roleAccessControl->listPermissions();
        $roles = $this->roleAccessControl->listRoles();

        $permissionData = array_map(fn ($p) => [
            'id'   => $p->getId(),
            'name' => $p->getName(),
        ], $permissions);

        $roleData = array_map(function ($role) {
            $permNames = [];
            foreach ($role->getPermissions() as $perm) {
                $permNames[] = $perm->getName();
            }
            return [
                'id'          => $role->getId(),
                'code'        => $role->getCode(),
                'name'        => $role->getName(),
                'permissions' => $permNames,
            ];
        }, $roles);

        return new JsonResponse([
            'permissions' => $permissionData,
            'roles'       => $roleData,
        ]);
    }

    #[OA\Delete(path: '/roles/{id}', tags: ['Roles'], summary: 'Eliminar rol')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 204, description: 'Rol eliminado')]
    #[OA\Response(response: 404, description: 'Rol no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function delete(int $id): Response
    {
        try {
            $this->roleAccessControl->deleteRole($id);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse(null, 204);
    }

    #[OA\Post(path: '/roles/{id}/permissions', tags: ['Roles'], summary: 'Asignar permisos a rol')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/AssignPermissionsRequest'))]
    #[OA\Response(response: 200, description: 'Permisos asignados')]
    #[OA\Response(response: 404, description: 'Rol o permiso no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function assignPermissions(int $id): Response
    {
        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => getError('VALIDATION_JSON_INVALID'), 'code' => 'VALIDATION_JSON_INVALID'], 400);
        }

        $permissions = $data['permissions'] ?? [];

        if (!is_array($permissions)) {
            return new JsonResponse(['error' => getError('VALIDATION_PERM_ARRAY'), 'code' => 'VALIDATION_PERM_ARRAY'], 422);
        }

        try {
            foreach ($permissions as $permissionName) {
                $this->roleAccessControl->assignPermissionToRole($id, $permissionName);
            }
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse(['message' => 'Permissions assigned']);
    }
}
