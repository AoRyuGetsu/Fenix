<?php

declare(strict_types=1);

namespace App\Features\User\Domain\Repositories;
use App\Features\User\Domain\Interfaces\UserRepositoryInterface;

use App\Features\People\Domain\Entities\PersonUser;
use App\Features\RolesPermisos\Domain\Entities\Role;
use App\Features\User\Application\DTO\UserQuery;
use App\Features\User\Domain\Entities\User;
use Doctrine\ORM\EntityManager;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function findById(int $id): ?User
    {
        return $this->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('u', 'pu', 'p')
            ->leftJoin('u.personUser', 'pu')
            ->leftJoin('pu.person', 'p')
            ->where('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);
    }

    public function findPaginated(UserQuery $query): array
    {
        $idQb = $this->entityManager->createQueryBuilder();
        $idQb->select('u.id')
           ->from(User::class, 'u');

        if ($query->includeDeleted !== true) {
            $idQb->where('u.deletedAt IS NULL');
        }

        if ($query->name !== null && $query->name !== '') {
            $idQb->join('u.personUser', 'pu')
               ->join('pu.person', 'p')
               ->andWhere($idQb->expr()->orX(
                   $idQb->expr()->like('p.firstName', ':name'),
                   $idQb->expr()->like('p.firstSurname', ':name'),
                   $idQb->expr()->like('p.middleName', ':name'),
                   $idQb->expr()->like('p.secondSurname', ':name'),
                   $idQb->expr()->like('p.documentNumber', ':name'),
               ))
               ->setParameter('name', '%' . $query->name . '%');
        }

        if ($query->role !== null) {
            $idQb->andWhere($idQb->expr()->exists(
                $this->entityManager->createQueryBuilder()
                    ->select('1')
                    ->from(Role::class, 'r')
                    ->join('r.users', 'ru')
                    ->where('ru.id = u.id')
                    ->andWhere('r.code = :role')
                    ->getDQL()
            ))->setParameter('role', $query->role);
        }

        if ($query->email !== null) {
            $idQb->andWhere('u.email = :email')
               ->setParameter('email', $query->email);
        }

        if ($query->active !== null) {
            $idQb->andWhere('u.active = :active')
               ->setParameter('active', $query->active);
        }

        $countQb = clone $idQb;
        $countQb->select('COUNT(u.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $ids = $idQb->orderBy('u.id', 'ASC')
           ->setFirstResult(($query->page - 1) * $query->perPage)
           ->setMaxResults($query->perPage)
           ->getQuery()
           ->getSingleColumnResult();

        if ($ids === []) {
            return [[], $total];
        }

        $data = $this->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('u', 'pu', 'p')
            ->leftJoin('u.personUser', 'pu')
            ->leftJoin('pu.person', 'p')
            ->where('u.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();

        return [$data, $total];
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

    public function countByRole(string $roleCode): int
    {
        return (int) $this->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.deletedAt IS NULL')
            ->andWhere($this->entityManager->createQueryBuilder()->expr()->exists(
                $this->entityManager->createQueryBuilder()
                    ->select('1')
                    ->from(Role::class, 'r')
                    ->join('r.users', 'ru')
                    ->where('ru.id = u.id')
                    ->andWhere('r.code = :roleCode')
                    ->getDQL()
            ))
            ->setParameter('roleCode', $roleCode)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
