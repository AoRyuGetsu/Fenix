<?php

declare(strict_types=1);

namespace App\Features\Auth\Controllers;

use App\Core\Contracts\ControllerInterface;
use App\Features\Auth\Contracts\RoleAccessControlServiceInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserRoleController implements ControllerInterface
{
    public function __construct(
        private RoleAccessControlServiceInterface $roleAccessControl,
        private Request $request,
    ) {}

    public function execute(): Response
    {
        return new JsonResponse(['error' => 'Not found'], 404);
    }

    #[OA\Post(path: '/users/{id}/roles', tags: ['User Roles'], summary: 'Asignar rol a usuario')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID del usuario', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/AssignRoleRequest'))]
    #[OA\Response(response: 200, description: 'Rol asignado')]
    #[OA\Response(response: 404, description: 'Usuario o rol no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function assign(int $userId): Response
    {
        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $roleName = $data['role'] ?? null;

        if ($roleName === null || !is_string($roleName)) {
            return new JsonResponse(['error' => 'role is required'], 422);
        }

        try {
            $this->roleAccessControl->assignRoleToUser($userId, $roleName);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        }

        return new JsonResponse(['message' => 'Role assigned']);
    }

    #[OA\Delete(path: '/users/{id}/roles/{roleId}', tags: ['User Roles'], summary: 'Quitar rol de usuario')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID del usuario', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'roleId', in: 'path', required: true, description: 'ID del rol', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 204, description: 'Rol quitado')]
    #[OA\Response(response: 404, description: 'Usuario o rol no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function remove(int $userId, int $roleId): Response
    {
        $role = $this->roleAccessControl->listRoles();
        $roleName = null;

        foreach ($role as $r) {
            if ($r->getId() === $roleId) {
                $roleName = $r->getName();
                break;
            }
        }

        if ($roleName === null) {
            return new JsonResponse(['error' => 'Role not found'], 404);
        }

        try {
            $this->roleAccessControl->removeRoleFromUser($userId, $roleName);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        }

        return new JsonResponse(null, 204);
    }
}
