<?php

declare(strict_types=1);

namespace App\Features\PlayerSpecification\Domain\Repositories;

use App\Features\PlayerSpecification\Application\DTO\PlayerSpecificationQuery;
use App\Features\PlayerSpecification\Domain\Entities\PlayerSpecification;
use App\Features\PlayerSpecification\Domain\Interfaces\PlayerSpecificationRepositoryInterface;
use Doctrine\ORM\EntityManager;

class PlayerSpecificationRepository implements PlayerSpecificationRepositoryInterface
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function findById(int $id): ?PlayerSpecification
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('ps')
            ->from(PlayerSpecification::class, 'ps')
            ->where('ps.user = :id')
            ->andWhere('ps.deletedAt IS NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUserId(int $userId): ?PlayerSpecification
    {
        return $this->findById($userId);
    }

    public function findPaginated(PlayerSpecificationQuery $query): array
    {
        $qb = $this->entityManager
            ->createQueryBuilder()
            ->select('ps')
            ->from(PlayerSpecification::class, 'ps')
            ->innerJoin('ps.user', 'u')
            ->andWhere('ps.deletedAt IS NULL')
            ->orderBy('ps.createdAt', 'DESC');

        if ($query->position !== null && $query->position !== '') {
            $qb->andWhere('ps.position = :position')
               ->setParameter('position', $query->position);
        }

        if ($query->status !== null && $query->status !== '') {
            $qb->andWhere('ps.status = :status')
               ->setParameter('status', $query->status);
        }

        if ($query->name !== null && $query->name !== '') {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('u.email', ':name'),
                )
            )->setParameter('name', '%' . $query->name . '%');
        }

        $total = (int) (clone $qb)
            ->select('COUNT(ps.user)')
            ->getQuery()
            ->getSingleScalarResult();

        $qb->setFirstResult(($query->page - 1) * $query->perPage)
           ->setMaxResults($query->perPage);

        return [$qb->getQuery()->getResult(), $total];
    }

    public function save(PlayerSpecification $spec): void
    {
        $this->entityManager->persist($spec);
        $this->entityManager->flush();
    }

    public function softDelete(PlayerSpecification $spec): void
    {
        $spec->setDeletedAt(new \DateTime());
        $this->entityManager->flush();
    }
}
