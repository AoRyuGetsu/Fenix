<?php

declare(strict_types=1);

namespace App\Features\RolesPermisos\Application\Controllers;

use App\Core\Interfaces\ControllerInterface;
use App\Features\RolesPermisos\Domain\Interfaces\RoleAccessControlServiceInterface;
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
        return new JsonResponse(['error' => getError('NOT_FOUND'), 'code' => 'NOT_FOUND'], 404);
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
            return new JsonResponse(['error' => getError('VALIDATION_JSON_INVALID'), 'code' => 'VALIDATION_JSON_INVALID'], 400);
        }

        $roleCode = $data['role'] ?? null;

        if ($roleCode === null || !is_string($roleCode)) {
            return new JsonResponse(['error' => getError('VALIDATION_ROLE_REQUIRED'), 'code' => 'VALIDATION_ROLE_REQUIRED'], 422);
        }

        try {
            $this->roleAccessControl->assignRoleToUser($userId, $roleCode);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
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
        $roles = $this->roleAccessControl->listRoles();
        $roleCode = null;

        foreach ($roles as $r) {
            if ($r->getId() === $roleId) {
                $roleCode = $r->getCode();
                break;
            }
        }

        if ($roleCode === null) {
            return new JsonResponse(['error' => getError('ROLE_NOT_FOUND'), 'code' => 'ROLE_NOT_FOUND'], 404);
        }

        try {
            $this->roleAccessControl->removeRoleFromUser($userId, $roleCode);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse(null, 204);
    }
}
