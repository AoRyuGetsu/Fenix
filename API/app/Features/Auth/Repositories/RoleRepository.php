<?php

declare(strict_types=1);

namespace App\Features\Auth\Repositories;

use App\Features\Auth\Models\Role;
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

    public function findByName(string $name): ?Role
    {
        return $this->entityManager
            ->getRepository(Role::class)
            ->findOneBy(['name' => $name]);
    }

    public function findAll(): array
    {
        return $this->entityManager
            ->getRepository(Role::class)
            ->findBy([], ['name' => 'ASC']);
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
