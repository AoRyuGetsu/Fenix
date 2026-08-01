<?php

declare(strict_types=1);

namespace App\Features\Representatives\Application\Controllers;

use App\Core\Env;
use App\Core\Interfaces\ControllerInterface;
use App\Features\Representatives\Application\DTO\RepPlayerQuery;
use App\Features\Representatives\Domain\Interfaces\RepPlayerServiceInterface;
use App\Features\User\Domain\Interfaces\UserRepositoryInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RepresentativeController implements ControllerInterface
{
    public function __construct(
        private RepPlayerServiceInterface $representativeService,
        private UserRepositoryInterface $userRepository,
        private Request $request,
    ) {}

    public function execute(): Response
    {
        return new JsonResponse(['error' => getError('NOT_FOUND'), 'code' => 'NOT_FOUND'], 404);
    }

    #[OA\Get(path: '/representatives/{repUserId}/players', tags: ['Representatives'], summary: 'Listar jugadores de un representante')]
    #[OA\Parameter(name: 'repUserId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'perPage', in: 'query', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Lista paginada de jugadores', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    public function listPlayers(int $repUserId): Response
    {
        $page = max(1, (int) ($this->request->query->get('page', '1')));
        $perPage = max(1, min(100, (int) ($this->request->query->get('perPage', Env::get('PAGINATION_PER_PAGE', '20')))));

        $query = new RepPlayerQuery(
            repUserId: $repUserId,
            page: $page,
            perPage: $perPage,
        );

        [$players, $total] = $this->representativeService->findPaginatedPlayers($query);

        $data = array_map(fn ($u) => $this->serializeUser($u), $players);

        return new JsonResponse([
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
        ]);
    }

    #[OA\Get(path: '/players/{playerUserId}/representative', tags: ['Representatives'], summary: 'Obtener representante de un jugador')]
    #[OA\Parameter(name: 'playerUserId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Representante del jugador con parentesco o null')]
    public function getRepresentative(int $playerUserId): Response
    {
        $assignment = $this->representativeService->getAssignment($playerUserId);
        if ($assignment === null) {
            return new JsonResponse(['data' => null]);
        }
        return new JsonResponse(['data' => $this->serializeAssignment($assignment)]);
    }

    #[OA\Post(path: '/representatives/{repUserId}/players', tags: ['Representatives'], summary: 'Asignar jugadores a un representante (reemplaza asignaciones)')]
    #[OA\Parameter(name: 'repUserId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(content: new OA\JsonContent(type: 'array', items: new OA\Items(properties: [
        new OA\Property(property: 'player_user_id', type: 'integer'),
        new OA\Property(property: 'parentesco_id', type: 'integer'),
    ], type: 'object')))]
    #[OA\Response(response: 200, description: 'Jugadores asignados')]
    public function assignPlayers(int $repUserId): Response
    {
        $data = json_decode($this->request->getContent(), true);
        $items = is_array($data) ? $data : [];

        try {
            $players = $this->representativeService->assignPlayers($repUserId, $items);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], 422);
        }

        return new JsonResponse([
            'data' => array_map(fn($u) => $this->serializeUser($u), $players),
        ]);
    }

    #[OA\Delete(path: '/representatives/{repUserId}/players/{playerUserId}', tags: ['Representatives'], summary: 'Remover un jugador de un representante')]
    #[OA\Parameter(name: 'repUserId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'playerUserId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Jugador removido')]
    public function removePlayer(int $repUserId, int $playerUserId): Response
    {
        try {
            $this->representativeService->removePlayer($repUserId, $playerUserId);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], 404);
        }
        return new JsonResponse(['message' => 'ok']);
    }

    private function serializeUser($user): array
    {
        $personUser = $user->getPersonUser();
        $displayName = $user->getEmail();
        if ($personUser !== null) {
            $person = $personUser->getPerson();
            $displayName = trim($person->getFirstName() . ' ' . $person->getFirstSurname());
        }
        return [
            'id'           => $user->getId(),
            'email'        => $user->getEmail(),
            'display_name' => $displayName,
        ];
    }

    private function serializeAssignment($assignment): array
    {
        $rep = $assignment->getRep();
        $parentesco = $assignment->getParentesco();
        $serialized = $this->serializeUser($rep);
        $serialized['parentesco'] = [
            'id'          => $parentesco->getId(),
            'code'        => $parentesco->getCode(),
            'description' => $parentesco->getDescription(),
        ];
        return $serialized;
    }
}
