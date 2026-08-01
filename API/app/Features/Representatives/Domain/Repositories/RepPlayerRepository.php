<?php

declare(strict_types=1);

namespace App\Features\Representatives\Domain\Repositories;

use App\Features\Representatives\Application\DTO\RepPlayerQuery;
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
            ->select('rp', 'rep', 'player', 'parentesco')
            ->leftJoin('rp.rep', 'rep')
            ->leftJoin('rp.player', 'player')
            ->leftJoin('rp.parentesco', 'parentesco')
            ->where('IDENTITY(rp.rep) = :repUserId')
            ->setParameter('repUserId', $repUserId)
            ->getQuery()
            ->getResult();
    }

    public function findPaginatedByRepUserId(RepPlayerQuery $query): array
    {
        $qb = $this->entityManager
            ->getRepository(RepPlayer::class)
            ->createQueryBuilder('rp')
            ->select('rp', 'rep', 'player', 'parentesco')
            ->leftJoin('rp.rep', 'rep')
            ->leftJoin('rp.player', 'player')
            ->leftJoin('rp.parentesco', 'parentesco')
            ->where('IDENTITY(rp.rep) = :repUserId')
            ->setParameter('repUserId', $query->repUserId);

        $total = (int) (clone $qb)->select('COUNT(rp.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $qb->setFirstResult(($query->page - 1) * $query->perPage)
            ->setMaxResults($query->perPage)
            ->getQuery()
            ->getResult();

        return [$items, $total];
    }

    public function findByPlayerUserId(int $playerUserId): ?RepPlayer
    {
        return $this->entityManager
            ->getRepository(RepPlayer::class)
            ->createQueryBuilder('rp')
            ->select('rp', 'rep', 'player', 'parentesco')
            ->leftJoin('rp.rep', 'rep')
            ->leftJoin('rp.player', 'player')
            ->leftJoin('rp.parentesco', 'parentesco')
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
