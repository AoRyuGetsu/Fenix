<?php

declare(strict_types=1);

namespace App\Features\Teams\Domain\Interfaces;

use App\Features\Teams\Application\DTO\TeamQueryDto;
use App\Features\Teams\Domain\Entities\Team;

interface TeamRepositoryInterface
{
    /** @return array{0: Team[], 1: int} */
    public function findPaginated(TeamQueryDto $query): array;

    public function findById(int $id): ?Team;

    public function findByTrainerId(int $trainerUserId): array;

    /** @return int[] */
    public function findPlayerIdsByTrainerId(int $trainerUserId): array;

    public function findByPlayerId(int $playerUserId): ?Team;

    public function save(Team $team): void;

    public function softDelete(Team $team): void;
}
