<?php

declare(strict_types=1);

namespace App\Features\Representatives\Domain\Interfaces;

use App\Features\Representatives\Domain\Entities\RepPlayer;

interface RepPlayerRepositoryInterface
{
    /** @return RepPlayer[] */
    public function findByRepUserId(int $repUserId): array;

    public function findByPlayerUserId(int $playerUserId): ?RepPlayer;

    public function save(RepPlayer $repPlayer): void;

    public function remove(RepPlayer $repPlayer): void;

    public function flush(): void;
}
