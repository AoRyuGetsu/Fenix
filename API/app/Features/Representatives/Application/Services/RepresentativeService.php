<?php

declare(strict_types=1);

namespace App\Features\Representatives\Application\Services;

use App\Core\AppException;
use App\Features\Representatives\Application\DTO\RepPlayerQuery;
use App\Features\Representatives\Domain\Entities\RepPlayer;
use App\Features\Representatives\Domain\Interfaces\RepPlayerRepositoryInterface;
use App\Features\User\Domain\Entities\User;
use App\Features\User\Domain\Interfaces\UserRepositoryInterface;

class RepresentativeService implements \App\Features\Representatives\Domain\Interfaces\RepPlayerServiceInterface
{
    public function __construct(
        private RepPlayerRepositoryInterface $repository,
        private UserRepositoryInterface $userRepository,
    ) {}

    public function findPaginatedPlayers(RepPlayerQuery $query): array
    {
        [$items, $total] = $this->repository->findPaginatedByRepUserId($query);
        $players = [];
        foreach ($items as $rp) {
            $player = $rp->getPlayer();
            if ($player !== null) {
                $players[] = $player;
            }
        }
        return [$players, $total];
    }

    /** @return int[] */
    public function getPlayerIds(int $repUserId): array
    {
        $items = $this->repository->findByRepUserId($repUserId);
        return array_map(fn(RepPlayer $rp) => $rp->getPlayer()->getId(), $items);
    }

    public function getRepresentative(int $playerUserId): ?User
    {
        $rp = $this->repository->findByPlayerUserId($playerUserId);
        return $rp?->getRep();
    }

    public function assignPlayerToRep(int $playerUserId, int $repUserId): void
    {
        $rep = $this->userRepository->findById($repUserId);
        if ($rep === null) {
            throw new AppException('REP_USER_NOT_FOUND');
        }
        $this->ensureUserHasRole($rep, 'REP', 'REP_USER_NOT_REP');

        $player = $this->userRepository->findById($playerUserId);
        if ($player === null) {
            throw new AppException('REP_PLAYER_NOT_FOUND');
        }
        $this->ensureUserHasRole($player, 'JUG', 'REP_PLAYER_NOT_JUG');

        $existing = $this->repository->findByPlayerUserId($playerUserId);
        if ($existing !== null) {
            $this->repository->remove($existing);
        }

        $rp = new RepPlayer($rep, $player);
        $this->repository->save($rp);
        $this->repository->flush();
    }

    public function assignPlayers(int $repUserId, array $playerIds): array
    {
        $rep = $this->userRepository->findById($repUserId);
        if ($rep === null) {
            throw new AppException('REP_USER_NOT_FOUND');
        }
        $this->ensureUserHasRole($rep, 'REP', 'REP_USER_NOT_REP');

        $players = [];
        foreach ($playerIds as $pid) {
            $player = $this->userRepository->findById((int) $pid);
            if ($player === null) {
                throw new AppException('REP_PLAYER_NOT_FOUND');
            }
            $this->ensureUserHasRole($player, 'JUG', 'REP_PLAYER_NOT_JUG');
            $players[] = $player;
        }

        // Remove existing assignments for this rep
        $existing = $this->repository->findByRepUserId($repUserId);
        foreach ($existing as $rp) {
            $this->repository->remove($rp);
        }

        // Create new assignments
        foreach ($players as $player) {
            $rp = new RepPlayer($rep, $player);
            $this->repository->save($rp);
        }

        $this->repository->flush();
        return $players;
    }

    public function removePlayer(int $repUserId, int $playerUserId): void
    {
        $existing = $this->repository->findByRepUserId($repUserId);
        $found = null;
        foreach ($existing as $rp) {
            if ($rp->getPlayer()->getId() === $playerUserId) {
                $found = $rp;
                break;
            }
        }
        if ($found === null) {
            throw new AppException('REP_ASSIGNMENT_NOT_FOUND');
        }
        $this->repository->remove($found);
        $this->repository->flush();
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
