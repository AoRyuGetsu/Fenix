<?php

declare(strict_types=1);

namespace App\Features\Auth\Controllers;

use App\Core\Contracts\ControllerInterface;
use App\Features\Auth\Contracts\RoleAccessControlServiceInterface;
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
            default => new JsonResponse(['error' => 'Method not allowed'], 405),
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
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $name = $data['name'] ?? null;

        if ($name === null || !is_string($name)) {
            return new JsonResponse(['error' => 'name is required'], 422);
        }

        try {
            $role = $this->roleAccessControl->createRole($name);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 409);
        }

        return new JsonResponse([
            'id'   => $role->getId(),
            'name' => $role->getName(),
        ], 201);
    }

    #[OA\Get(path: '/roles', tags: ['Roles'], summary: 'Listar roles')]
    #[OA\Response(response: 200, description: 'Lista de roles', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Role')))]
    public function list(): Response
    {
        $roles = $this->roleAccessControl->listRoles();

        $data = array_map(fn ($role) => [
            'id'   => $role->getId(),
            'name' => $role->getName(),
        ], $roles);

        return new JsonResponse($data);
    }

    #[OA\Delete(path: '/roles/{id}', tags: ['Roles'], summary: 'Eliminar rol')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 204, description: 'Rol eliminado')]
    #[OA\Response(response: 404, description: 'Rol no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function delete(int $id): Response
    {
        try {
            $this->roleAccessControl->deleteRole($id);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
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
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $permissions = $data['permissions'] ?? [];

        if (!is_array($permissions)) {
            return new JsonResponse(['error' => 'permissions must be an array'], 422);
        }

        try {
            foreach ($permissions as $permissionName) {
                $this->roleAccessControl->assignPermissionToRole($id, $permissionName);
            }
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        }

        return new JsonResponse(['message' => 'Permissions assigned']);
    }
}
