<?php

declare(strict_types=1);

namespace App\Features\Representatives\Domain\Repositories;

use App\Features\Representatives\Domain\Entities\RepPlayer;
use App\Features\Representatives\Domain\Interfaces\RepPlayerRepositoryInterface;
use Doctrine\ORM\EntityManager;

class RepPlayerRepository implements RepPlayerRepositoryInterface
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function findByRepUserId(int $repUserId): array
    {
        return $this->entityManager
            ->getRepository(RepPlayer::class)
            ->createQueryBuilder('rp')
            ->select('rp', 'rep', 'player')
            ->leftJoin('rp.rep', 'rep')
            ->leftJoin('rp.player', 'player')
            ->where('IDENTITY(rp.rep) = :repUserId')
            ->setParameter('repUserId', $repUserId)
            ->getQuery()
            ->getResult();
    }

    public function findByPlayerUserId(int $playerUserId): ?RepPlayer
    {
        return $this->entityManager
            ->getRepository(RepPlayer::class)
            ->createQueryBuilder('rp')
            ->select('rp', 'rep', 'player')
            ->leftJoin('rp.rep', 'rep')
            ->leftJoin('rp.player', 'player')
            ->where('IDENTITY(rp.player) = :playerUserId')
            ->setParameter('playerUserId', $playerUserId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(RepPlayer $repPlayer): void
    {
        $this->entityManager->persist($repPlayer);
    }

    public function remove(RepPlayer $repPlayer): void
    {
        $this->entityManager->remove($repPlayer);
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }
}
