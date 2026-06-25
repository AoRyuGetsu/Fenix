<?php

declare(strict_types=1);

namespace App\Features\User\Controllers;

use App\Core\Contracts\ControllerInterface;
use App\Features\User\Contracts\UserQuery;
use App\Features\User\Contracts\UserServiceInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController implements ControllerInterface
{
    public function __construct(
        private UserServiceInterface $userService,
        private Request $request,
    ) {}

    public function execute(): Response
    {
        return match ($this->request->getMethod()) {
            'POST'   => $this->create(),
            'GET'    => $this->list(),
            default  => new JsonResponse(['error' => 'Method not allowed'], 405),
        };
    }

    #[OA\Post(path: '/users', tags: ['Users'], summary: 'Crear usuario')]
    #[OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/UserCreate'))]
    #[OA\Response(response: 201, description: 'Usuario creado', content: new OA\JsonContent(ref: '#/components/schemas/User'))]
    #[OA\Response(response: 422, description: 'Datos inválidos', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    private function create(): Response
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

        $user = $this->userService->create($email, $password);

        return new JsonResponse([
            'id'    => $user->getId(),
            'email' => $user->getEmail(),
        ], 201);
    }

    #[OA\Get(path: '/users', tags: ['Users'], summary: 'Listar usuarios')]
    #[OA\Parameter(name: 'email', in: 'query', description: 'Filtrar por email', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Lista de usuarios', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/User')))]
    private function list(): Response
    {
        $query = new UserQuery(
            email: $this->request->query->get('email'),
        );

        $users = $this->userService->list($query);

        $data = array_map(fn ($user) => [
            'id'         => $user->getId(),
            'email'      => $user->getEmail(),
            'created_at' => $user->getCreatedAt()->format('c'),
            'updated_at' => $user->getUpdatedAt()->format('c'),
        ], $users);

        return new JsonResponse($data);
    }

    #[OA\Get(path: '/users/{id}', tags: ['Users'], summary: 'Obtener usuario por ID')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Usuario encontrado', content: new OA\JsonContent(ref: '#/components/schemas/User'))]
    #[OA\Response(response: 404, description: 'Usuario no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function show(int $id): Response
    {
        $user = $this->userService->findById($id);

        if ($user === null) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        return new JsonResponse([
            'id'         => $user->getId(),
            'email'      => $user->getEmail(),
            'created_at' => $user->getCreatedAt()->format('c'),
            'updated_at' => $user->getUpdatedAt()->format('c'),
        ]);
    }

    #[OA\Put(path: '/users/{id}', tags: ['Users'], summary: 'Actualizar usuario')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(content: new OA\JsonContent(properties: [
        new OA\Property(property: 'email', type: 'string'),
        new OA\Property(property: 'password', type: 'string'),
    ]))]
    #[OA\Response(response: 200, description: 'Usuario actualizado', content: new OA\JsonContent(ref: '#/components/schemas/User'))]
    #[OA\Response(response: 404, description: 'Usuario no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function update(int $id): Response
    {
        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        try {
            $user = $this->userService->update($id, $data);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        }

        return new JsonResponse([
            'id'         => $user->getId(),
            'email'      => $user->getEmail(),
            'updated_at' => $user->getUpdatedAt()->format('c'),
        ]);
    }

    #[OA\Delete(path: '/users/{id}', tags: ['Users'], summary: 'Eliminar usuario (soft delete)')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 204, description: 'Usuario eliminado')]
    #[OA\Response(response: 404, description: 'Usuario no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function delete(int $id): Response
    {
        try {
            $this->userService->softDelete($id);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        }

        return new JsonResponse(null, 204);
    }
}
