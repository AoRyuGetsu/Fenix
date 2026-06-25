<?php

declare(strict_types=1);

namespace App\Features\Auth\Controllers;

use App\Core\Contracts\ControllerInterface;
use App\Features\Auth\Contracts\AuthServiceInterface;
use App\Features\Auth\Contracts\RoleAccessControlServiceInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController implements ControllerInterface
{
    public function __construct(
        private AuthServiceInterface $authService,
        private RoleAccessControlServiceInterface $roleAccessControl,
        private Request $request,
    ) {}

    public function execute(): Response
    {
        return match ($this->request->getMethod()) {
            'POST' => match ($this->request->getPathInfo()) {
                '/auth/login'    => $this->login(),
                '/auth/refresh'  => $this->refresh(),
                default          => new JsonResponse(['error' => 'Not found'], 404),
            },
            'GET' => match ($this->request->getPathInfo()) {
                '/auth/me' => $this->me(),
                default    => new JsonResponse(['error' => 'Not found'], 404),
            },
            default => new JsonResponse(['error' => 'Method not allowed'], 405),
        };
    }

    #[OA\Post(path: '/auth/login', tags: ['Auth'], summary: 'Iniciar sesión')]
    #[OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/LoginRequest'))]
    #[OA\Response(response: 200, description: 'Login exitoso', content: new OA\JsonContent(ref: '#/components/schemas/AuthResponse'))]
    #[OA\Response(response: 401, description: 'Credenciales inválidas', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function login(): Response
    {
        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if ($email === null || $password === null) {
            return new JsonResponse(['error' => 'email and password are required'], 422);
        }

        try {
            $result = $this->authService->login($email, $password);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }

        return new JsonResponse($result);
    }

    #[OA\Post(path: '/auth/refresh', tags: ['Auth'], summary: 'Renovar token')]
    #[OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/RefreshTokenRequest'))]
    #[OA\Response(response: 200, description: 'Token renovado', content: new OA\JsonContent(ref: '#/components/schemas/AuthResponse'))]
    #[OA\Response(response: 401, description: 'Token inválido', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function refresh(): Response
    {
        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $refreshToken = $data['refresh_token'] ?? null;

        if ($refreshToken === null) {
            return new JsonResponse(['error' => 'refresh_token is required'], 422);
        }

        try {
            $result = $this->authService->refreshToken($refreshToken);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }

        return new JsonResponse($result);
    }

    #[OA\Get(path: '/auth/me', tags: ['Auth'], summary: 'Obtener usuario autenticado')]
    #[OA\Parameter(name: 'Authorization', in: 'header', required: true, description: 'Bearer token', schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Datos del usuario autenticado', content: new OA\JsonContent(ref: '#/components/schemas/MeResponse'))]
    #[OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function me(): Response
    {
        $authHeader = $this->request->headers->get('Authorization');

        if ($authHeader === null || !str_starts_with($authHeader, 'Bearer ')) {
            return new JsonResponse(['error' => 'Missing or invalid Authorization header'], 401);
        }

        $token = substr($authHeader, 7);

        try {
            $claims = $this->authService->validateToken($token);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }

        $userId = (int) ($claims['sub'] ?? 0);

        $roles = $this->roleAccessControl->getUserRoles($userId);
        $permissions = $this->roleAccessControl->getUserPermissions($userId);
        $menus = $this->roleAccessControl->getUserMenus($userId);

        return new JsonResponse([
            'id'          => $userId,
            'email'       => $claims['email'] ?? null,
            'roles'       => array_map(fn ($r) => $r->getName(), $roles),
            'permissions' => array_map(fn ($p) => $p->getName(), $permissions),
            'menus' => array_map(fn ($m) => [
                'id'         => $m->getId(),
                'label'      => $m->getLabel(),
                'icon'       => $m->getIcon(),
                'route'      => $m->getRoute(),
                'parent_id'  => $m->getParent()?->getId(),
                'menu_order' => $m->getMenuOrder(),
            ], $menus),
        ]);
    }
}
