<?php

declare(strict_types=1);

namespace App\Features\RolesPermisos\Domain\Repositories;
use App\Features\RolesPermisos\Domain\Interfaces\RoleRepositoryInterface;

use App\Features\RolesPermisos\Application\DTO\RoleQuery;
use App\Features\RolesPermisos\Domain\Entities\Role;
use Doctrine\ORM\EntityManager;

class RoleRepository implements RoleRepositoryInterface
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function findById(int $id): ?Role
    {
        return $this->entityManager->find(Role::class, $id);
    }

    public function findByCode(string $code): ?Role
    {
        return $this->entityManager
            ->getRepository(Role::class)
            ->createQueryBuilder('r')
            ->where('r.code = :code')
            ->andWhere('r.deletedAt IS NULL')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAll(): array
    {
        return $this->entityManager
            ->getRepository(Role::class)
            ->createQueryBuilder('r')
            ->where('r.deletedAt IS NULL')
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPaginated(RoleQuery $query): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('r')
           ->from(Role::class, 'r')
           ->where('r.deletedAt IS NULL');

        if ($query->name !== null) {
            $qb->andWhere('r.name LIKE :name')
               ->setParameter('name', '%' . $query->name . '%');
        }

        $countQb = clone $qb;
        $countQb->select('COUNT(r.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $qb->setFirstResult(($query->page - 1) * $query->perPage)
           ->setMaxResults($query->perPage)
           ->orderBy('r.name', 'ASC');

        $data = $qb->getQuery()->getResult();

        return [$data, $total];
    }

    public function findByUser(int $userId): array
    {
        $query = $this->entityManager->createQuery(
            'SELECT r FROM ' . Role::class . ' r '
            . 'JOIN r.users u WHERE u.id = :userId'
        );
        $query->setParameter('userId', $userId);

        return $query->getResult();
    }

    public function findByUsers(array $userIds): array
    {
        $query = $this->entityManager->createQuery(
            'SELECT r, u FROM ' . Role::class . ' r '
            . 'JOIN r.users u WHERE u.id IN (:userIds)'
        );
        $query->setParameter('userIds', $userIds);

        return $query->getResult();
    }

    public function save(Role $role): void
    {
        $this->entityManager->persist($role);
        $this->entityManager->flush();
    }

    public function delete(Role $role): void
    {
        $this->entityManager->remove($role);
        $this->entityManager->flush();
    }
}
