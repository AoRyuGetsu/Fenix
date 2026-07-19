<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Domain\Repositories;

use App\Features\Questionnaire\Application\DTO\QuestionnaireQuery;
use App\Features\Questionnaire\Domain\Interfaces\QuestionnaireRepositoryInterface;
use App\Features\Questionnaire\Domain\Entities\Questionnaire;
use Doctrine\ORM\EntityManager;

class QuestionnaireRepository implements QuestionnaireRepositoryInterface
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function save(Questionnaire $questionnaire): void
    {
        $this->entityManager->persist($questionnaire);
        $this->entityManager->flush();
    }

    public function findById(int $id): ?Questionnaire
    {
        return $this->entityManager
            ->getRepository(Questionnaire::class)
            ->createQueryBuilder('q')
            ->select('q', 'rr', 'tr', 's', 'qs', 'o')
            ->leftJoin('q.responderRole', 'rr')
            ->leftJoin('q.targetRole', 'tr')
            ->leftJoin('q.sections', 's')
            ->leftJoin('s.questions', 'qs')
            ->leftJoin('qs.options', 'o')
            ->where('q.id = :id')
            ->andWhere('q.deletedAt IS NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPaginated(QuestionnaireQuery $query): array
    {
        $idQb = $this->entityManager
            ->getRepository(Questionnaire::class)
            ->createQueryBuilder('q')
            ->select('q.id')
            ->andWhere('q.deletedAt IS NULL');

        if ($query->title !== null) {
            $idQb->andWhere('q.title LIKE :title')
               ->setParameter('title', '%' . $query->title . '%');
        }

        if ($query->active !== null) {
            $idQb->andWhere('q.active = :active')
               ->setParameter('active', $query->active);
        }

        if ($query->responderRoleId !== null) {
            $idQb->join('q.responderRole', 'rr')
               ->andWhere('rr.id = :responderRole')
               ->setParameter('responderRole', $query->responderRoleId);
        }

        if ($query->targetRoleId !== null) {
            $idQb->join('q.targetRole', 'tr')
               ->andWhere('tr.id = :targetRole')
               ->setParameter('targetRole', $query->targetRoleId);
        }

        $total = (clone $idQb)->select('COUNT(q.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $ids = $idQb->orderBy('q.createdAt', 'DESC')
           ->setFirstResult(($query->page - 1) * $query->perPage)
           ->setMaxResults($query->perPage)
           ->getQuery()
           ->getSingleColumnResult();

        if ($ids === []) {
            return [[], (int) $total];
        }

        $data = $this->entityManager
            ->getRepository(Questionnaire::class)
            ->createQueryBuilder('q')
            ->select('q', 'rr', 'tr', 's', 'qs', 'o')
            ->leftJoin('q.responderRole', 'rr')
            ->leftJoin('q.targetRole', 'tr')
            ->leftJoin('q.sections', 's')
            ->leftJoin('s.questions', 'qs')
            ->leftJoin('qs.options', 'o')
            ->where('q.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('q.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return [$data, (int) $total];
    }

    public function findByResponderRoles(array $roleIds, ?QuestionnaireQuery $query = null): array
    {
        $query = $query ?? new QuestionnaireQuery();

        $idQb = $this->entityManager
            ->getRepository(Questionnaire::class)
            ->createQueryBuilder('q')
            ->select('q.id')
            ->andWhere('q.deletedAt IS NULL');

        if ($roleIds !== []) {
            $idQb->join('q.responderRole', 'rr')
               ->andWhere('rr.id IN (:roleIds)')
               ->setParameter('roleIds', $roleIds);
        } else {
            $idQb->andWhere('1 = 0');
        }

        if ($query->title !== null) {
            $idQb->andWhere('q.title LIKE :title')
               ->setParameter('title', '%' . $query->title . '%');
        }

        if ($query->active !== null) {
            $idQb->andWhere('q.active = :active')
               ->setParameter('active', $query->active);
        }

        $total = (clone $idQb)->select('COUNT(q.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $ids = $idQb->orderBy('q.createdAt', 'DESC')
           ->setFirstResult(($query->page - 1) * $query->perPage)
           ->setMaxResults($query->perPage)
           ->getQuery()
           ->getSingleColumnResult();

        if ($ids === []) {
            return [[], (int) $total];
        }

        $data = $this->entityManager
            ->getRepository(Questionnaire::class)
            ->createQueryBuilder('q')
            ->select('q', 'rr', 'tr', 's', 'qs', 'o')
            ->leftJoin('q.responderRole', 'rr')
            ->leftJoin('q.targetRole', 'tr')
            ->leftJoin('q.sections', 's')
            ->leftJoin('s.questions', 'qs')
            ->leftJoin('qs.options', 'o')
            ->where('q.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('q.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return [$data, (int) $total];
    }

    public function softDelete(Questionnaire $questionnaire): void
    {
        $questionnaire->setDeletedAt(new \DateTime());
        $this->entityManager->flush();
    }

    public function countAll(): int
    {
        return (int) $this->entityManager
            ->getRepository(Questionnaire::class)
            ->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('q.deletedAt IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
