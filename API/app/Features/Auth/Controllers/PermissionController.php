<?php

declare(strict_types=1);

namespace App\Features\Auth\Controllers;

use App\Core\Contracts\ControllerInterface;
use App\Features\Auth\Contracts\RoleAccessControlServiceInterface;
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
            default => new JsonResponse(['error' => 'Method not allowed'], 405),
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
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $name = $data['name'] ?? null;

        if ($name === null || !is_string($name)) {
            return new JsonResponse(['error' => 'name is required'], 422);
        }

        try {
            $permission = $this->roleAccessControl->createPermission($name);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 409);
        }

        return new JsonResponse([
            'id'   => $permission->getId(),
            'name' => $permission->getName(),
        ], 201);
    }

    #[OA\Get(path: '/permissions', tags: ['Permissions'], summary: 'Listar permisos')]
    #[OA\Response(response: 200, description: 'Lista de permisos', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Permission')))]
    public function list(): Response
    {
        $permissions = $this->roleAccessControl->listPermissions();

        $data = array_map(fn ($p) => [
            'id'   => $p->getId(),
            'name' => $p->getName(),
        ], $permissions);

        return new JsonResponse($data);
    }
}
