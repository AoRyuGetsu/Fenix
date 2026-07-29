<?php

declare(strict_types=1);

namespace App\Features\Representatives\Domain\Interfaces;

use App\Features\Representatives\Application\DTO\RepPlayerQuery;
use App\Features\User\Domain\Entities\User;

interface RepPlayerServiceInterface
{
    public function findPaginatedPlayers(RepPlayerQuery $query): array;
    public function getPlayerIds(int $repUserId): array;
    public function getRepresentative(int $playerUserId): ?User;
    public function assignPlayerToRep(int $playerUserId, int $repUserId): void;
    public function assignPlayers(int $repUserId, array $playerIds): array;
    public function removePlayer(int $repUserId, int $playerUserId): void;
}
