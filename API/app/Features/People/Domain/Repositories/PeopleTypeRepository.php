<?php

declare(strict_types=1);

namespace App\Features\People\Domain\Repositories;
use App\Features\People\Domain\Interfaces\PeopleTypeRepositoryInterface;

use App\Features\People\Application\DTO\PeopleTypeQuery;
use App\Features\People\Domain\Entities\PeopleType;
use Doctrine\ORM\EntityManager;

class PeopleTypeRepository implements PeopleTypeRepositoryInterface
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function findAll(): array
    {
        return $this->entityManager
            ->getRepository(PeopleType::class)
            ->findBy(['deletedAt' => null], ['id' => 'ASC']);
    }

    public function findPaginated(PeopleTypeQuery $query): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('pt')
           ->from(PeopleType::class, 'pt')
           ->where('pt.deletedAt IS NULL');

        if ($query->code !== null) {
            $qb->andWhere('pt.code LIKE :code')
               ->setParameter('code', '%' . $query->code . '%');
        }

        if ($query->description !== null) {
            $qb->andWhere('pt.description LIKE :description')
               ->setParameter('description', '%' . $query->description . '%');
        }

        $countQb = clone $qb;
        $countQb->select('COUNT(pt.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $qb->setFirstResult(($query->page - 1) * $query->perPage)
           ->setMaxResults($query->perPage)
           ->orderBy('pt.id', 'ASC');

        $data = $qb->getQuery()->getResult();

        return [$data, $total];
    }

    public function findByCode(string $code): ?PeopleType
    {
        return $this->entityManager
            ->getRepository(PeopleType::class)
            ->findOneBy(['code' => $code, 'deletedAt' => null]);
    }
}
