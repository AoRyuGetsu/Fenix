<?php

declare(strict_types=1);

namespace App\Features\Representatives\Application\Controllers;

use App\Core\Interfaces\ControllerInterface;
use App\Features\Representatives\Application\Services\RepresentativeService;
use App\Features\User\Domain\Interfaces\UserRepositoryInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RepresentativeController implements ControllerInterface
{
    public function __construct(
        private RepresentativeService $representativeService,
        private UserRepositoryInterface $userRepository,
        private Request $request,
    ) {}

    public function execute(): Response
    {
        return new JsonResponse(['error' => 'Not found'], 404);
    }

    #[OA\Get(path: '/representatives/{repUserId}/players', tags: ['Representatives'], summary: 'Listar jugadores de un representante')]
    #[OA\Parameter(name: 'repUserId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Lista de jugadores')]
    public function listPlayers(int $repUserId): Response
    {
        $items = $this->representativeService->getPlayerIds($repUserId);
        $players = [];
        foreach ($items as $playerId) {
            $user = $this->userRepository->findById($playerId);
            if ($user !== null) {
                $players[] = $this->serializeUser($user);
            }
        }
        return new JsonResponse(['data' => $players, 'total' => count($players)]);
    }

    #[OA\Get(path: '/players/{playerUserId}/representative', tags: ['Representatives'], summary: 'Obtener representante de un jugador')]
    #[OA\Parameter(name: 'playerUserId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Representante del jugador o null')]
    public function getRepresentative(int $playerUserId): Response
    {
        $rep = $this->representativeService->getRepresentative($playerUserId);
        if ($rep === null) {
            return new JsonResponse(['data' => null]);
        }
        return new JsonResponse(['data' => $this->serializeUser($rep)]);
    }

    #[OA\Post(path: '/representatives/{repUserId}/players', tags: ['Representatives'], summary: 'Asignar jugadores a un representante (reemplaza asignaciones)')]
    #[OA\Parameter(name: 'repUserId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(content: new OA\JsonContent(properties: [
        new OA\Property(property: 'player_ids', type: 'array', items: new OA\Items(type: 'integer')),
    ]))]
    #[OA\Response(response: 200, description: 'Jugadores asignados')]
    public function assignPlayers(int $repUserId): Response
    {
        $data = json_decode($this->request->getContent(), true) ?? [];
        $playerIds = $data['player_ids'] ?? [];

        try {
            $players = $this->representativeService->assignPlayers($repUserId, $playerIds);
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
}
