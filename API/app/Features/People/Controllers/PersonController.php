<?php

declare(strict_types=1);

namespace App\Features\People\Controllers;

use App\Core\Contracts\ControllerInterface;
use App\Features\Auth\Contracts\AuthServiceInterface;
use App\Features\People\Contracts\PersonQuery;
use App\Features\People\Contracts\PersonServiceInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PersonController implements ControllerInterface
{
    public function __construct(
        private PersonServiceInterface $personService,
        private AuthServiceInterface $authService,
        private Request $request,
    ) {}

    private function requireAdmin(): ?Response
    {
        $authHeader = $this->request->headers->get('Authorization');

        if ($authHeader === null || !str_starts_with($authHeader, 'Bearer ')) {
            return new JsonResponse(['error' => 'No autenticado'], 401);
        }

        $token = substr($authHeader, 7);

        try {
            $claims = $this->authService->validateToken($token);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }

        $roles = $claims['roles'] ?? [];

        if (!in_array('administrador', $roles, true)) {
            return new JsonResponse(['error' => 'Solo administradores'], 403);
        }

        return null;
    }

    public function execute(): Response
    {
        return match ($this->request->getMethod()) {
            'POST'   => $this->create(),
            'GET'    => $this->list(),
            default  => new JsonResponse(['error' => 'Method not allowed'], 405),
        };
    }

    #[OA\Post(path: '/people', tags: ['People'], summary: 'Create person')]
    #[OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/PersonCreate'))]
    #[OA\Response(response: 201, description: 'Person created', content: new OA\JsonContent(ref: '#/components/schemas/Person'))]
    #[OA\Response(response: 422, description: 'Invalid data', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    private function create(): Response
    {
        $error = $this->requireAdmin();
        if ($error !== null) {
            return $error;
        }

        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $required = ['first_name', 'first_surname', 'id_number', 'type'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || !is_string($data[$field])) {
                return new JsonResponse(['error' => "$field is required"], 422);
            }
        }

        $allowedTypes = ['supervisor', 'entrenador', 'jugador', 'representante'];
        if (!in_array($data['type'], $allowedTypes, true)) {
            return new JsonResponse(['error' => 'Invalid type. Must be: ' . implode(', ', $allowedTypes)], 422);
        }

        $createUser = (bool) ($data['create_user'] ?? true);

        if ($createUser && (!isset($data['email']) || !is_string($data['email']))) {
            return new JsonResponse(['error' => 'email is required when create_user is true'], 422);
        }

        try {
            $person = $this->personService->create($data);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 409);
        }

        return new JsonResponse($this->serialize($person), 201);
    }

    #[OA\Get(path: '/people', tags: ['People'], summary: 'List people')]
    #[OA\Parameter(name: 'type', in: 'query', description: 'Filter by type', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'id_number', in: 'query', description: 'Filter by ID number', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'List of people', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Person')))]
    private function list(): Response
    {
        $query = new PersonQuery(
            type: $this->request->query->get('type'),
            idNumber: $this->request->query->get('id_number'),
            search: $this->request->query->get('search'),
        );

        $people = $this->personService->list($query);

        $data = array_map(fn ($p) => $this->serialize($p), $people);

        return new JsonResponse($data);
    }

    #[OA\Get(path: '/people/{id}', tags: ['People'], summary: 'Get person by ID')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Person found', content: new OA\JsonContent(ref: '#/components/schemas/Person'))]
    #[OA\Response(response: 404, description: 'Person not found', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function show(int $id): Response
    {
        $person = $this->personService->findById($id);

        if ($person === null) {
            return new JsonResponse(['error' => 'Person not found'], 404);
        }

        return new JsonResponse($this->serialize($person));
    }

    #[OA\Put(path: '/people/{id}', tags: ['People'], summary: 'Update person')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/PersonUpdate'))]
    #[OA\Response(response: 200, description: 'Person updated', content: new OA\JsonContent(ref: '#/components/schemas/Person'))]
    #[OA\Response(response: 404, description: 'Person not found', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function update(int $id): Response
    {
        $error = $this->requireAdmin();
        if ($error !== null) {
            return $error;
        }

        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        if (isset($data['type'])) {
            $allowedTypes = ['supervisor', 'entrenador', 'jugador', 'representante'];
            if (!in_array($data['type'], $allowedTypes, true)) {
                return new JsonResponse(['error' => 'Invalid type'], 422);
            }
        }

        try {
            $person = $this->personService->update($id, $data);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        }

        return new JsonResponse($this->serialize($person));
    }

    #[OA\Delete(path: '/people/{id}', tags: ['People'], summary: 'Delete person (soft delete)')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 204, description: 'Person deleted')]
    #[OA\Response(response: 404, description: 'Person not found', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function delete(int $id): Response
    {
        $error = $this->requireAdmin();
        if ($error !== null) {
            return $error;
        }

        try {
            $this->personService->softDelete($id);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        }

        return new JsonResponse(null, 204);
    }

    private function serialize($person): array
    {
        return [
            'id'            => $person->getId(),
            'user_id'       => $person->getUser()?->getId(),
            'type'          => $person->getType(),
            'first_name'    => $person->getFirstName(),
            'middle_name'   => $person->getMiddleName(),
            'first_surname' => $person->getFirstSurname(),
            'second_surname'=> $person->getSecondSurname(),
            'id_number'     => $person->getIdNumber(),
            'email'         => $person->getEmail(),
            'phone'         => $person->getPhone(),
            'address'       => $person->getAddress(),
            'create_user'   => $person->getUser() !== null,
            'created_at'    => $person->getCreatedAt()->format('c'),
            'updated_at'    => $person->getUpdatedAt()->format('c'),
        ];
    }
}
