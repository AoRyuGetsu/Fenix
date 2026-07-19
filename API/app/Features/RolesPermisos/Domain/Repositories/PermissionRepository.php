<?php

declare(strict_types=1);

namespace App\Features\RolesPermisos\Domain\Repositories;
use App\Features\RolesPermisos\Domain\Interfaces\PermissionRepositoryInterface;

use App\Features\RolesPermisos\Application\DTO\PermissionQuery;
use App\Features\RolesPermisos\Domain\Entities\Permission;
use Doctrine\ORM\EntityManager;

class PermissionRepository implements PermissionRepositoryInterface
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function findById(int $id): ?Permission
    {
        return $this->entityManager->find(Permission::class, $id);
    }

    public function findByName(string $name): ?Permission
    {
        return $this->entityManager
            ->getRepository(Permission::class)
            ->findOneBy(['name' => $name]);
    }

    public function findAll(): array
    {
        return $this->entityManager
            ->getRepository(Permission::class)
            ->findBy([], ['name' => 'ASC']);
    }

    public function findPaginated(PermissionQuery $query): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('p')
           ->from(Permission::class, 'p')
           ->where('p.deletedAt IS NULL');

        if ($query->name !== null) {
            $qb->andWhere('p.name LIKE :name')
               ->setParameter('name', '%' . $query->name . '%');
        }

        $countQb = clone $qb;
        $countQb->select('COUNT(p.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $qb->setFirstResult(($query->page - 1) * $query->perPage)
           ->setMaxResults($query->perPage)
           ->orderBy('p.name', 'ASC');

        $data = $qb->getQuery()->getResult();

        return [$data, $total];
    }

    public function findByUser(int $userId): array
    {
        $query = $this->entityManager->createQuery(
            'SELECT p FROM ' . Permission::class . ' p '
            . 'JOIN p.roles r '
            . 'JOIN r.users u WHERE u.id = :userId'
        );
        $query->setParameter('userId', $userId);

        return $query->getResult();
    }

    public function save(Permission $permission): void
    {
        $this->entityManager->persist($permission);
        $this->entityManager->flush();
    }

    public function delete(Permission $permission): void
    {
        $this->entityManager->remove($permission);
        $this->entityManager->flush();
    }
}
