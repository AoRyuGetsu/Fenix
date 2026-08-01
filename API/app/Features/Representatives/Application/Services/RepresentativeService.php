<?php

declare(strict_types=1);

namespace App\Features\Representatives\Application\Services;

use App\Core\AppException;
use App\Features\Parentescos\Domain\Interfaces\ParentescoServiceInterface;
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
        private ParentescoServiceInterface $parentescoService,
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

    public function getAssignment(int $playerUserId): ?RepPlayer
    {
        return $this->repository->findByPlayerUserId($playerUserId);
    }

    public function assignPlayerToRep(int $playerUserId, int $repUserId, int $parentescoId): void
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

        $parentesco = $this->parentescoService->findById($parentescoId);
        if ($parentesco === null) {
            throw new AppException('PARENTESCO_NOT_FOUND');
        }

        $existing = $this->repository->findByPlayerUserId($playerUserId);
        if ($existing !== null) {
            $this->repository->remove($existing);
            $this->repository->flush();
        }

        $rp = new RepPlayer($rep, $player, $parentesco);
        $this->repository->save($rp);
        $this->repository->flush();
    }

    public function assignPlayers(int $repUserId, array $items): array
    {
        $rep = $this->userRepository->findById($repUserId);
        if ($rep === null) {
            throw new AppException('REP_USER_NOT_FOUND');
        }
        $this->ensureUserHasRole($rep, 'REP', 'REP_USER_NOT_REP');

        $players = [];
        foreach ($items as $item) {
            $playerUserId = (int) ($item['player_user_id'] ?? 0);
            $parentescoId = (int) ($item['parentesco_id'] ?? 0);

            $player = $this->userRepository->findById($playerUserId);
            if ($player === null) {
                throw new AppException('REP_PLAYER_NOT_FOUND');
            }
            $this->ensureUserHasRole($player, 'JUG', 'REP_PLAYER_NOT_JUG');

            $parentesco = $this->parentescoService->findById($parentescoId);
            if ($parentesco === null) {
                throw new AppException('PARENTESCO_NOT_FOUND');
            }

            $players[] = [$player, $parentesco];
        }

        // Remove existing assignments for this rep
        $existing = $this->repository->findByRepUserId($repUserId);
        foreach ($existing as $rp) {
            $this->repository->remove($rp);
        }

        // Release players that currently belong to another rep
        foreach ($players as [$player, $parentesco]) {
            $current = $this->repository->findByPlayerUserId((int) $player->getId());
            if ($current !== null && $current->getRep()->getId() !== $repUserId) {
                $this->repository->remove($current);
            }
        }

        $this->repository->flush();

        // Create new assignments
        foreach ($players as [$player, $parentesco]) {
            $rp = new RepPlayer($rep, $player, $parentesco);
            $this->repository->save($rp);
        }

        $this->repository->flush();
        return array_map(fn ($pair) => $pair[0], $players);
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
