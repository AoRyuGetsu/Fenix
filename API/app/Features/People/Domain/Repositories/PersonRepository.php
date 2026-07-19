<?php

declare(strict_types=1);

namespace App\Features\People\Domain\Repositories;
use App\Features\People\Domain\Interfaces\PersonRepositoryInterface;

use App\Features\People\Application\DTO\PersonQuery;
use App\Features\People\Domain\Entities\Person;
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

    public function findByDocumentNumber(string $documentNumber): ?Person
    {
        return $this->entityManager
            ->getRepository(Person::class)
            ->findOneBy(['documentNumber' => $documentNumber]);
    }

    public function findPaginated(PersonQuery $query): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('p')
           ->from(Person::class, 'p')
           ->where('p.deletedAt IS NULL');

        if ($query->type !== null) {
            $peopleType = $this->entityManager
                ->getRepository(\App\Features\People\Domain\Entities\PeopleType::class)
                ->findOneBy(['code' => $query->type]);
            if ($peopleType !== null) {
                $qb->andWhere('p.peopleType = :peopleType')
                   ->setParameter('peopleType', $peopleType);
            }
        }

        if ($query->documentNumber !== null) {
            $qb->andWhere('p.documentNumber = :documentNumber')
               ->setParameter('documentNumber', $query->documentNumber);
        }

        if ($query->search !== null) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('p.firstName', ':search'),
                $qb->expr()->like('p.firstSurname', ':search'),
                $qb->expr()->like('p.documentNumber', ':search'),
            ))->setParameter('search', '%' . $query->search . '%');
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
