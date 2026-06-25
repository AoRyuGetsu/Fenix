<?php

declare(strict_types=1);

namespace App\Features\Auth\Repositories;

use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
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

    public function findByRole(int $roleId): array
    {
        $query = $this->entityManager->createQuery(
            'SELECT p FROM ' . Permission::class . ' p '
            . 'JOIN p.roles r WHERE r.id = :roleId'
        );
        $query->setParameter('roleId', $roleId);

        return $query->getResult();
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
