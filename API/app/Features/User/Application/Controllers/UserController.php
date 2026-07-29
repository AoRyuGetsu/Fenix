<?php

declare(strict_types=1);

namespace App\Features\User\Application\Controllers;

use App\Core\AppException;
use App\Core\Env;
use App\Core\Interfaces\ControllerInterface;
use App\Features\Representatives\Domain\Interfaces\RepPlayerServiceInterface;
use App\Features\RolesPermisos\Domain\Interfaces\RoleAccessControlServiceInterface;
use App\Features\Teams\Domain\Interfaces\TeamServiceInterface;
use App\Features\Teams\Domain\Interfaces\TeamRepositoryInterface;
use App\Features\User\Application\DTO\UserQuery;
use App\Features\User\Domain\Interfaces\UserServiceInterface;
use App\Features\User\Domain\Entities\User;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController implements ControllerInterface
{
    public function __construct(
        private UserServiceInterface $userService,
        private RoleAccessControlServiceInterface $roleAccessControl,
        private Request $request,
        private TeamServiceInterface $teamService,
        private TeamRepositoryInterface $teamRepository,
        private RepPlayerServiceInterface $representativeService,
    ) {}

    public function execute(): Response
    {
        return match ($this->request->getMethod()) {
            'POST'   => $this->create(),
            'GET'    => $this->list(),
            default  => new JsonResponse(['error' => getError('METHOD_NOT_ALLOWED'), 'code' => 'METHOD_NOT_ALLOWED'], 405),
        };
    }

    #[OA\Post(path: '/users', tags: ['Users'], summary: 'Crear usuario (con o sin persona)')]
    #[OA\RequestBody(content: new OA\JsonContent(properties: [
        new OA\Property(property: 'email', type: 'string'),
        new OA\Property(property: 'password', type: 'string'),
        new OA\Property(property: 'person', ref: '#/components/schemas/PersonCreate'),
        new OA\Property(property: 'team_id', type: 'integer', nullable: true),
        new OA\Property(property: 'representative_id', type: 'integer', nullable: true),
    ]))]
    #[OA\Response(response: 201, description: 'Usuario creado', content: new OA\JsonContent(ref: '#/components/schemas/User'))]
    #[OA\Response(response: 422, description: 'Datos inválidos', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    private function create(): Response
    {
        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => getError('VALIDATION_JSON_INVALID'), 'code' => 'VALIDATION_JSON_INVALID'], 400);
        }

        if (!isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse(['error' => getError('VALIDATION_EMAIL_REQUIRED'), 'code' => 'VALIDATION_EMAIL_REQUIRED'], 422);
        }

        $personData = $data['person'] ?? null;

        if ($personData !== null) {
            $required = ['first_name', 'first_surname', 'document_number', 'email', 'type'];
            foreach ($required as $field) {
                if (!isset($personData[$field]) || !is_string($personData[$field])) {
                    return new JsonResponse(['error' => getError('VALIDATION_REQUIRED'), 'code' => 'VALIDATION_REQUIRED'], 422);
                }
            }

            $type = $personData['type'];
            if ($type === 'JUG' && !isset($data['team_id'])) {
                return new JsonResponse([
                    'error' => getError('TEAM_REQUIRED_FOR_JUG'),
                    'code'  => 'TEAM_REQUIRED_FOR_JUG',
                ], 422);
            }
        }

        try {
            $user = $this->userService->create($data['email'], $data['password'], $personData);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        $roleCode = $personData['type'] ?? 'ADM';

        try {
            $this->roleAccessControl->assignRoleToUser((int) $user->getId(), $roleCode);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        if (isset($data['team_id'])) {
            try {
                $this->teamService->addPlayer((int) $data['team_id'], $user);
            } catch (\App\Core\AppException $e) {
                return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], 422);
            }
        }

        if (isset($data['representative_id'])) {
            try {
                $this->representativeService->assignPlayerToRep((int) $user->getId(), (int) $data['representative_id']);
            } catch (\App\Core\AppException $e) {
                return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], 422);
            }
        }

        return new JsonResponse($this->serialize($user), 201);
    }

    #[OA\Get(path: '/users', tags: ['Users'], summary: 'Listar usuarios')]
    #[OA\Parameter(name: 'email', in: 'query', description: 'Filtrar por email', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'role', in: 'query', description: 'Filtrar por nombre de rol', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'includeDeleted', in: 'query', description: 'Incluir usuarios eliminados (soft delete)', required: false, schema: new OA\Schema(type: 'boolean'))]
    #[OA\Parameter(name: 'page', in: 'query', description: 'Número de página', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'perPage', in: 'query', description: 'Elementos por página', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Lista paginada de usuarios', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    private function list(): Response
    {
        $page = (int) ($this->request->query->get('page', '1'));
        $perPage = (int) ($this->request->query->get('perPage', Env::get('PAGINATION_PER_PAGE', '20')));

        $query = new UserQuery(
            name: $this->request->query->get('name'),
            email: $this->request->query->get('email'),
            active: $this->request->query->get('active') !== null ? (bool) $this->request->query->get('active') : null,
            role: $this->request->query->get('role'),
            includeDeleted: $this->request->query->get('includeDeleted') !== null ? (bool) $this->request->query->get('includeDeleted') : null,
            unassigned: $this->request->query->get('unassigned') !== null ? (bool) $this->request->query->get('unassigned') : null,
            page: max(1, $page),
            perPage: max(1, min(100, $perPage)),
        );

        [$users, $total] = $this->userService->list($query);

        $userIds = array_map(fn ($u) => (int) $u->getId(), $users);
        $rolesByUser = $userIds !== [] ? $this->roleAccessControl->getRolesForUsers($userIds) : [];

        $data = array_map(fn ($user) => $this->serialize($user, $rolesByUser[(int) $user->getId()] ?? []), $users);

        return new JsonResponse([
            'data'    => $data,
            'total'   => $total,
            'page'    => $query->page,
            'perPage' => $query->perPage,
        ]);
    }

    #[OA\Get(path: '/users/{id}', tags: ['Users'], summary: 'Obtener usuario por ID')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Usuario encontrado', content: new OA\JsonContent(ref: '#/components/schemas/User'))]
    #[OA\Response(response: 404, description: 'Usuario no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function show(int $id): Response
    {
        $user = $this->userService->findById($id);

        if ($user === null) {
            return new JsonResponse(['error' => getError('USER_NOT_FOUND'), 'code' => 'USER_NOT_FOUND'], 404);
        }

        return new JsonResponse($this->serialize($user));
    }

    #[OA\Put(path: '/users/{id}', tags: ['Users'], summary: 'Actualizar usuario')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(content: new OA\JsonContent(properties: [
        new OA\Property(property: 'email', type: 'string'),
        new OA\Property(property: 'password', type: 'string', description: 'Contraseña'),
        new OA\Property(property: 'active', type: 'boolean'),
        new OA\Property(property: 'person', ref: '#/components/schemas/PersonUpdate'),
        new OA\Property(property: 'team_id', type: 'integer', nullable: true),
        new OA\Property(property: 'representative_id', type: 'integer', nullable: true),
    ]))]
    #[OA\Response(response: 200, description: 'Usuario actualizado', content: new OA\JsonContent(ref: '#/components/schemas/User'))]
    #[OA\Response(response: 404, description: 'Usuario no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function update(int $id): Response
    {
        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => getError('VALIDATION_JSON_INVALID'), 'code' => 'VALIDATION_JSON_INVALID'], 400);
        }

        try {
            $user = $this->userService->update($id, $data);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        if (array_key_exists('team_id', $data)) {
            $currentTeam = $this->teamRepository->findByPlayerId($id);
            if ($currentTeam !== null) {
                $this->teamService->removePlayer($currentTeam->getId(), $id);
            }
            if ($data['team_id'] !== null) {
                $this->teamService->addPlayer((int) $data['team_id'], $user);
            }
        }

        if (array_key_exists('representative_id', $data)) {
            $currentRep = $this->representativeService->getRepresentative($id);
            if ($currentRep !== null) {
                $this->representativeService->removePlayer((int) $currentRep->getId(), $id);
            }
            if ($data['representative_id'] !== null) {
                $this->representativeService->assignPlayerToRep($id, (int) $data['representative_id']);
            }
        }

        return new JsonResponse($this->serialize($user));
    }

    #[OA\Delete(path: '/users/{id}', tags: ['Users'], summary: 'Eliminar usuario (soft delete)')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 204, description: 'Usuario eliminado')]
    #[OA\Response(response: 404, description: 'Usuario no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function delete(int $id): Response
    {
        try {
            $this->userService->softDelete($id);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse(null, 204);
    }

    #[OA\Put(path: '/users/{id}/restore', tags: ['Users'], summary: 'Restaurar usuario (soft delete revert)')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Usuario restaurado', content: new OA\JsonContent(ref: '#/components/schemas/User'))]
    #[OA\Response(response: 404, description: 'Usuario no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function restore(int $id): Response
    {
        try {
            $this->userService->restore($id);
            $user = $this->userService->findById($id);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse($this->serialize($user));
    }

    private function serialize(User $user, array $roles = []): array
    {
        $person = $user->getPerson();
        if ($roles === []) {
            $roles = $this->roleAccessControl->getUserRoles((int) $user->getId());
        }
        $roleCodes = array_map(fn ($r) => $r->getCode(), $roles);

        $result = [
            'id'         => $user->getId(),
            'email'      => $user->getEmail(),
            'active'     => $user->isActive(),
            'roles'      => $roleCodes,
            'deleted_at' => $user->getDeletedAt()?->format('c'),
            'created_at' => $user->getCreatedAt()->format('c'),
            'updated_at' => $user->getUpdatedAt()->format('c'),
        ];

        $team = $this->teamRepository->findByPlayerId((int) $user->getId());
        $result['team'] = $team !== null ? [
            'id'   => $team->getId(),
            'name' => $team->getName(),
        ] : null;

        $rep = $this->representativeService->getRepresentative((int) $user->getId());
        $result['representative'] = $rep !== null ? [
            'id'           => $rep->getId(),
            'display_name' => $this->getUserDisplayName($rep),
        ] : null;

        if ($person !== null) {
            $result['person'] = [
                'id'              => $person->getId(),
                'type'            => $person->getPeopleType()->getCode(),
                'first_name'      => $person->getFirstName(),
                'middle_name'     => $person->getMiddleName(),
                'first_surname'   => $person->getFirstSurname(),
                'second_surname'  => $person->getSecondSurname(),
                'document_number' => $person->getDocumentNumber(),
                'email'           => $person->getEmail(),
                'phone'           => $person->getPhone(),
                'address'         => $person->getAddress(),
            ];
        }

        return $result;
    }

    private function getUserDisplayName(User $user): string
    {
        $personUser = $user->getPersonUser();
        if ($personUser !== null) {
            $person = $personUser->getPerson();
            return trim($person->getFirstName() . ' ' . $person->getFirstSurname());
        }
        return $user->getEmail();
    }
}
