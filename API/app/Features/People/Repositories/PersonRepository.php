<?php

declare(strict_types=1);

namespace App\Features\People\Repositories;

use App\Features\People\Models\Person;
use App\Features\People\Models\PersonUser;
use Doctrine\ORM\EntityManager;

class PersonRepository implements PersonRepositoryInterface
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function findById(int $id): ?Person
    {
        return $this->entityManager->find(Person::class, $id);
    }

    public function findByUserId(int $userId): ?Person
    {
        $personUser = $this->entityManager
            ->getRepository(PersonUser::class)
            ->findOneBy(['user' => $userId]);

        return $personUser?->getPerson();
    }

    public function findByIdNumber(string $idNumber): ?Person
    {
        return $this->entityManager
            ->getRepository(Person::class)
            ->findOneBy(['idNumber' => $idNumber]);
    }

    public function findAll(array $criteria = []): array
    {
        return $this->entityManager
            ->getRepository(Person::class)
            ->findBy($criteria);
    }

    public function save(Person $person): void
    {
        $this->entityManager->persist($person);
        $this->entityManager->flush();
    }

    public function softDelete(Person $person): void
    {
        $person->setDeletedAt(new \DateTime());
        $this->entityManager->flush();
    }
}
