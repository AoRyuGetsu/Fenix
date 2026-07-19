<?php

declare(strict_types=1);

namespace App\Features\Periods\Domain\Repositories;

use App\Features\Periods\Application\DTO\PeriodQuery;
use App\Features\Periods\Domain\Entities\Period;
use App\Features\Periods\Domain\Interfaces\PeriodRepositoryInterface;
use Doctrine\ORM\EntityManager;

class PeriodRepository implements PeriodRepositoryInterface
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function findAll(): array
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('p')
            ->from(Period::class, 'p')
            ->andWhere('p.deletedAt IS NULL')
            ->orderBy('p.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findById(int $id): ?Period
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('p')
            ->from(Period::class, 'p')
            ->where('p.id = :id')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPaginated(PeriodQuery $query): array
    {
        $qb = $this->entityManager
            ->createQueryBuilder()
            ->select('p')
            ->from(Period::class, 'p')
            ->andWhere('p.deletedAt IS NULL')
            ->orderBy('p.startDate', 'DESC');

        if ($query->name !== null && $query->name !== '') {
            $qb->andWhere('p.name LIKE :name')
               ->setParameter('name', '%' . $query->name . '%');
        }

        $total = (int) (clone $qb)
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $qb->setFirstResult(($query->page - 1) * $query->perPage)
           ->setMaxResults($query->perPage);

        return [$qb->getQuery()->getResult(), $total];
    }

    public function findByName(string $name): ?Period
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('p')
            ->from(Period::class, 'p')
            ->where('p.name = :name')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(Period $period): void
    {
        $this->entityManager->persist($period);
        $this->entityManager->flush();
    }

    public function softDelete(Period $period): void
    {
        $period->setDeletedAt(new \DateTime());
        $this->entityManager->flush();
    }
}
