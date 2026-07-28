<?php

declare(strict_types=1);

namespace App\Features\Teams\Application\Services;

use App\Core\AppException;
use App\Features\Teams\Application\DTO\TeamQueryDto;
use App\Features\Teams\Domain\Entities\Team;
use App\Features\Teams\Domain\Interfaces\TeamRepositoryInterface;
use App\Features\User\Domain\Entities\User;
use App\Features\User\Domain\Interfaces\UserRepositoryInterface;
use App\Infrastructure\Auth\AuthContext;
use App\Core\Env;

class TeamService
{
    public function __construct(
        private TeamRepositoryInterface $repository,
        private UserRepositoryInterface $userRepository,
        private AuthContext $authContext,
    ) {}

    public function list(?TeamQueryDto $query = null): array
    {
        $q = $query ?? new TeamQueryDto();
        return $this->repository->findPaginated($q);
    }

    public function findById(int $id): ?Team
    {
        return $this->repository->findById($id);
    }

    public function create(array $data): Team
    {
        $trainer = $this->userRepository->findById((int) ($data['trainer_user_id'] ?? 0));
        if ($trainer === null) {
            throw new AppException('TEAM_TRAINER_NOT_FOUND');
        }
        $this->ensureUserHasRole($trainer, 'ENT', 'TEAM_TRAINER_NOT_ENT');

        $team = new Team();
        $team->setName((string) ($data['name'] ?? ''));
        $team->setCode((string) ($data['code'] ?? ''));
        $team->setDescription($data['description'] ?? null);
        $team->setTrainer($trainer);

        if (isset($data['player_ids']) && is_array($data['player_ids'])) {
            foreach ($data['player_ids'] as $pid) {
                $player = $this->userRepository->findById((int) $pid);
                if ($player === null) {
                    throw new AppException('TEAM_PLAYER_NOT_FOUND');
                }
                $this->ensureUserHasRole($player, 'JUG', 'TEAM_PLAYER_NOT_JUG');
                $team->addPlayer($player);
            }
        }

        $currentUser = $this->userRepository->findById($this->authContext->getUserId());
        $team->setCreatedBy($currentUser);
        $team->setUpdatedBy($currentUser);
        $team->setCreatedAt(new \DateTime());
        $team->setUpdatedAt(new \DateTime());

        $this->repository->save($team);
        return $team;
    }

    public function update(int $id, array $data): Team
    {
        $team = $this->repository->findById($id);
        if ($team === null) {
            throw new AppException('TEAM_NOT_FOUND');
        }

        if (isset($data['name'])) {
            $team->setName((string) $data['name']);
        }
        if (isset($data['code'])) {
            $team->setCode((string) $data['code']);
        }
        if (array_key_exists('description', $data)) {
            $team->setDescription($data['description']);
        }
        if (isset($data['trainer_user_id'])) {
            $trainer = $this->userRepository->findById((int) $data['trainer_user_id']);
            if ($trainer === null) {
                throw new AppException('TEAM_TRAINER_NOT_FOUND');
            }
            $this->ensureUserHasRole($trainer, 'ENT', 'TEAM_TRAINER_NOT_ENT');
            $team->setTrainer($trainer);
        }
        if (isset($data['player_ids']) && is_array($data['player_ids'])) {
            $players = [];
            foreach ($data['player_ids'] as $pid) {
                $player = $this->userRepository->findById((int) $pid);
                if ($player === null) {
                    throw new AppException('TEAM_PLAYER_NOT_FOUND');
                }
                $this->ensureUserHasRole($player, 'JUG', 'TEAM_PLAYER_NOT_JUG');
                $players[] = $player;
            }
            $team->setPlayers($players);
        }

        $currentUser = $this->userRepository->findById($this->authContext->getUserId());
        $team->setUpdatedBy($currentUser);
        $team->setUpdatedAt(new \DateTime());

        $this->repository->save($team);
        return $team;
    }

    public function addPlayer(int $teamId, User $player): Team
    {
        $team = $this->repository->findById($teamId);
        if ($team === null) {
            throw new AppException('TEAM_NOT_FOUND');
        }
        $this->ensureUserHasRole($player, 'JUG', 'TEAM_PLAYER_NOT_JUG');
        $team->addPlayer($player);

        $currentUser = $this->userRepository->findById($this->authContext->getUserId());
        $team->setUpdatedBy($currentUser);
        $team->setUpdatedAt(new \DateTime());
        $this->repository->save($team);
        return $team;
    }

    public function removePlayer(int $teamId, int $playerUserId): void
    {
        $team = $this->repository->findById($teamId);
        if ($team === null) {
            throw new AppException('TEAM_NOT_FOUND');
        }
        $player = $this->userRepository->findById($playerUserId);
        if ($player === null) {
            throw new AppException('TEAM_PLAYER_NOT_FOUND');
        }
        $team->removePlayer($player);

        $currentUser = $this->userRepository->findById($this->authContext->getUserId());
        $team->setUpdatedBy($currentUser);
        $team->setUpdatedAt(new \DateTime());
        $this->repository->save($team);
    }

    public function softDelete(int $id): void
    {
        $team = $this->repository->findById($id);
        if ($team === null) {
            throw new AppException('TEAM_NOT_FOUND');
        }
        $this->repository->softDelete($team);
    }

    private function ensureUserHasRole(User $user, string $roleCode, string $errorCode): void
    {
        $hasRole = false;
        foreach ($user->getRoles() as $role) {
            if ($role->getCode() === $roleCode) {
                $hasRole = true;
                break;
            }
        }
        if (!$hasRole) {
            throw new AppException($errorCode);
        }
    }
}
