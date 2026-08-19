<?php

declare(strict_types=1);

namespace App\Features\Teams\Domain\Interfaces;

use App\Features\Teams\Application\DTO\TeamQueryDto;
use App\Features\Teams\Domain\Entities\Team;
use App\Features\User\Domain\Entities\User;

interface TeamServiceInterface
{
    public function list(?TeamQueryDto $query = null): array;
    public function findById(int $id): ?Team;
    public function findByIdIfViewable(int $id): ?Team;
    public function findByTrainerId(int $trainerUserId): array;
    public function findMyTeams(?TeamQueryDto $query = null): array;
    public function create(array $data): Team;
    public function update(int $id, array $data): Team;
    public function addPlayer(int $teamId, User $player): Team;
    public function removePlayer(int $teamId, int $playerUserId): void;
    public function softDelete(int $id): void;
}
