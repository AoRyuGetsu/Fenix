<?php

declare(strict_types=1);

namespace App\Features\Representatives\Domain\Interfaces;

use App\Features\Representatives\Application\DTO\RepPlayerQuery;
use App\Features\Representatives\Domain\Entities\RepPlayer;
use App\Features\User\Domain\Entities\User;

interface RepPlayerServiceInterface
{
    public function findPaginatedPlayers(RepPlayerQuery $query): array;
    public function getPlayerIds(int $repUserId): array;
    public function getRepresentative(int $playerUserId): ?User;
    public function getAssignment(int $playerUserId): ?RepPlayer;
    public function assignPlayerToRep(int $playerUserId, int $repUserId, int $parentescoId): void;
    public function assignPlayers(int $repUserId, array $items): array;
    public function removePlayer(int $repUserId, int $playerUserId): void;
}
