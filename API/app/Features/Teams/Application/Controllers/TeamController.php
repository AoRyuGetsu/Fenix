<?php

declare(strict_types=1);

namespace App\Features\Teams\Application\Controllers;

use App\Core\Env;
use App\Core\Interfaces\ControllerInterface;
use App\Features\Teams\Application\DTO\TeamQueryDto;
use App\Features\Teams\Domain\Interfaces\TeamServiceInterface;
use App\Features\User\Application\DTO\UserQuery;
use App\Features\User\Domain\Interfaces\UserRepositoryInterface;
use App\Infrastructure\Auth\AuthContext;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TeamController implements ControllerInterface
{
    public function __construct(
        private TeamServiceInterface $teamService,
        private UserRepositoryInterface $userRepository,
        private Request $request,
        private AuthContext $authContext,
    ) {}

    public function execute(): Response
    {
        return new JsonResponse(['error' => getError('NOT_FOUND'), 'code' => 'NOT_FOUND'], 404);
    }

    #[OA\Get(path: '/teams', tags: ['Teams'], summary: 'Listar equipos paginados')]
    #[OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'perPage', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Lista paginada de equipos')]
    public function list(): Response
    {
        $page = max(1, (int) ($this->request->query->get('page', '1')));
        $perPage = max(1, min(100, (int) ($this->request->query->get('perPage', Env::get('PAGINATION_PER_PAGE', '20')))));

        $query = new TeamQueryDto(
            search: $this->request->query->get('search'),
            page: $page,
            perPage: $perPage,
        );

        [$items, $total] = $this->teamService->list($query);

        $data = array_map(fn ($t) => [
            'id'            => $t->getId(),
            'name'          => $t->getName(),
            'code'          => $t->getCode(),
            'description'   => $t->getDescription(),
            'trainer'       => $this->serializeTrainer($t->getTrainer()),
            'player_count'  => $t->getPlayers()->count(),
            'created_at'    => $t->getCreatedAt()->format('c'),
            'updated_at'    => $t->getUpdatedAt()->format('c'),
        ], $items);

        return new JsonResponse([
            'data'    => $data,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
        ]);
    }

    #[OA\Get(path: '/teams/my', tags: ['Teams'], summary: 'Obtener equipos del entrenador logueado (paginados)')]
    #[OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'perPage', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Lista paginada de equipos del entrenador')]
    public function listMyTeams(): Response
    {
        $page = max(1, (int) ($this->request->query->get('page', '1')));
        $perPage = max(1, min(100, (int) ($this->request->query->get('perPage', Env::get('PAGINATION_PER_PAGE', '20')))));

        $query = new TeamQueryDto(
            search: $this->request->query->get('search'),
            page: $page,
            perPage: $perPage,
        );

        [$teams, $total] = $this->teamService->findMyTeams($query);

        $data = array_map(fn ($t) => [
            'id'   => $t->getId(),
            'name' => $t->getName(),
            'code' => $t->getCode(),
        ], $teams);

        return new JsonResponse([
            'data'    => $data,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
        ]);
    }

    #[OA\Get(path: '/teams/{id}', tags: ['Teams'], summary: 'Obtener un equipo')]
    #[OA\Response(response: 200, description: 'Equipo')]
    #[OA\Response(response: 404, description: 'No encontrado')]
    public function show(int $id): Response
    {
        try {
            $team = $this->teamService->findByIdIfViewable($id);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        if ($team === null) {
            return new JsonResponse(['error' => getError('TEAM_NOT_FOUND'), 'code' => 'TEAM_NOT_FOUND'], 404);
        }

        return new JsonResponse([
            'id'            => $team->getId(),
            'name'          => $team->getName(),
            'code'          => $team->getCode(),
            'description'   => $team->getDescription(),
            'trainer'       => $this->serializeTrainer($team->getTrainer()),
            'players'       => array_map(fn ($p) => $this->serializePlayer($p), $team->getPlayers()->toArray()),
            'player_count'  => $team->getPlayers()->count(),
            'created_at'    => $team->getCreatedAt()->format('c'),
            'updated_at'    => $team->getUpdatedAt()->format('c'),
        ]);
    }

    #[OA\Post(path: '/teams', tags: ['Teams'], summary: 'Crear equipo')]
    #[OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/TeamCreate'))]
    #[OA\Response(response: 201, description: 'Equipo creado')]
    public function create(): Response
    {
        $data = json_decode($this->request->getContent(), true) ?? [];

        try {
            $team = $this->teamService->create($data);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], 422);
        }

        return new JsonResponse([
            'id'            => $team->getId(),
            'name'          => $team->getName(),
            'code'          => $team->getCode(),
            'description'   => $team->getDescription(),
            'trainer'       => $this->serializeTrainer($team->getTrainer()),
            'player_count'  => $team->getPlayers()->count(),
            'created_at'    => $team->getCreatedAt()->format('c'),
            'updated_at'    => $team->getUpdatedAt()->format('c'),
        ], 201);
    }

    #[OA\Put(path: '/teams/{id}', tags: ['Teams'], summary: 'Actualizar equipo')]
    #[OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/TeamCreate'))]
    #[OA\Response(response: 200, description: 'Equipo actualizado')]
    public function update(int $id): Response
    {
        $data = json_decode($this->request->getContent(), true) ?? [];

        try {
            $team = $this->teamService->update($id, $data);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], 422);
        }

        return new JsonResponse([
            'id'            => $team->getId(),
            'name'          => $team->getName(),
            'code'          => $team->getCode(),
            'description'   => $team->getDescription(),
            'trainer'       => $this->serializeTrainer($team->getTrainer()),
            'player_count'  => $team->getPlayers()->count(),
            'created_at'    => $team->getCreatedAt()->format('c'),
            'updated_at'    => $team->getUpdatedAt()->format('c'),
        ]);
    }

    #[OA\Delete(path: '/teams/{id}', tags: ['Teams'], summary: 'Eliminar equipo (soft delete)')]
    #[OA\Response(response: 200, description: 'Equipo eliminado')]
    public function delete(int $id): Response
    {
        try {
            $this->teamService->softDelete($id);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse(['message' => getError('TEAM_DELETED')]);
    }

    private function serializeTrainer($user): array
    {
        return [
            'id'    => $user->getId(),
            'email' => $user->getEmail(),
            'display_name' => $this->getUserDisplayName($user),
        ];
    }

    private function serializePlayer($user): array
    {
        return [
            'id'    => $user->getId(),
            'email' => $user->getEmail(),
            'display_name' => $this->getUserDisplayName($user),
        ];
    }

    private function getUserDisplayName($user): string
    {
        $personUser = $user->getPersonUser();
        if ($personUser !== null) {
            $person = $personUser->getPerson();
            return trim($person->getFirstName() . ' ' . $person->getFirstSurname());
        }
        return $user->getEmail();
    }
}
