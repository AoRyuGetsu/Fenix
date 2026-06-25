<?php

declare(strict_types=1);

namespace App\Features\User\Repositories;

use App\Features\User\Models\User;
use Doctrine\ORM\EntityManager;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function findById(int $id): ?User
    {
        return $this->entityManager->find(User::class, $id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);
    }

    public function findAll(array $criteria = []): array
    {
        return $this->entityManager
            ->getRepository(User::class)
            ->findBy($criteria);
    }

    public function save(User $user): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function softDelete(User $user): void
    {
        $user->setDeletedAt(new \DateTime());
        $this->entityManager->flush();
    }
}
