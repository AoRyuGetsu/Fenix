<?php

declare(strict_types=1);

namespace App\Features\Parentescos\Domain\Repositories;

use App\Features\Parentescos\Application\DTO\ParentescoQuery;
use App\Features\Parentescos\Domain\Entities\Parentesco;
use App\Features\Parentescos\Domain\Interfaces\ParentescoRepositoryInterface;
use Doctrine\ORM\EntityManager;

class ParentescoRepository implements ParentescoRepositoryInterface
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function findAll(): array
    {
        return $this->entityManager
            ->getRepository(Parentesco::class)
            ->findBy(['deletedAt' => null], ['id' => 'ASC']);
    }

    public function findPaginated(ParentescoQuery $query): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('p')
           ->from(Parentesco::class, 'p')
           ->where('p.deletedAt IS NULL');

        if ($query->code !== null) {
            $qb->andWhere('p.code LIKE :code')
               ->setParameter('code', '%' . $query->code . '%');
        }

        if ($query->description !== null) {
            $qb->andWhere('p.description LIKE :description')
               ->setParameter('description', '%' . $query->description . '%');
        }

        $countQb = clone $qb;
        $countQb->select('COUNT(p.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $qb->setFirstResult(($query->page - 1) * $query->perPage)
           ->setMaxResults($query->perPage)
           ->orderBy('p.id', 'ASC');

        $data = $qb->getQuery()->getResult();

        return [$data, $total];
    }

    public function findByCode(string $code): ?Parentesco
    {
        return $this->entityManager
            ->getRepository(Parentesco::class)
            ->findOneBy(['code' => $code, 'deletedAt' => null]);
    }

    public function findById(int $id): ?Parentesco
    {
        return $this->entityManager
            ->getRepository(Parentesco::class)
            ->findOneBy(['id' => $id, 'deletedAt' => null]);
    }
}
