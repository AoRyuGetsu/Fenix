<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Domain\Repositories;

use App\Features\Questionnaire\Application\DTO\EvaluationQuery;
use App\Features\Questionnaire\Domain\Entities\Answer;
use App\Features\Questionnaire\Domain\Entities\Evaluation;
use App\Features\Questionnaire\Domain\Interfaces\EvaluationRepositoryInterface;
use Doctrine\ORM\EntityManager;

class EvaluationRepository implements EvaluationRepositoryInterface
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function save(Evaluation $evaluation): void
    {
        $this->entityManager->persist($evaluation);
        $this->entityManager->flush();
    }

    public function findById(int $id): ?Evaluation
    {
        return $this->entityManager
            ->getRepository(Evaluation::class)
            ->createQueryBuilder('e')
            ->select('e', 'q', 'p', 'ru', 'rup', 'rupp', 'tu', 'tup', 'tupp', 'a', 'aq')
            ->leftJoin('e.questionnaire', 'q')
            ->leftJoin('e.period', 'p')
            ->leftJoin('e.responderUser', 'ru')
            ->leftJoin('ru.personUser', 'rup')
            ->leftJoin('rup.person', 'rupp')
            ->leftJoin('e.targetUser', 'tu')
            ->leftJoin('tu.personUser', 'tup')
            ->leftJoin('tup.person', 'tupp')
            ->leftJoin('e.answers', 'a', 'WITH', 'a.deletedAt IS NULL')
            ->leftJoin('a.question', 'aq')
            ->where('e.id = :id')
            ->andWhere('e.deletedAt IS NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPaginated(EvaluationQuery $query): array
    {
        $idQb = $this->entityManager
            ->getRepository(Evaluation::class)
            ->createQueryBuilder('e')
            ->select('e.id')
            ->andWhere('e.deletedAt IS NULL');

        if ($query->responderUserId !== null) {
            $idQb->andWhere('IDENTITY(e.responderUser) = :responderUserId')
               ->setParameter('responderUserId', $query->responderUserId);
        }

        if ($query->questionnaireId !== null) {
            $idQb->andWhere('IDENTITY(e.questionnaire) = :questionnaireId')
               ->setParameter('questionnaireId', $query->questionnaireId);
        }

        if ($query->targetUserId !== null) {
            $idQb->andWhere('IDENTITY(e.targetUser) = :targetUserId')
               ->setParameter('targetUserId', $query->targetUserId);
        }

        if ($query->periodId !== null) {
            $idQb->andWhere('IDENTITY(e.period) = :periodId')
               ->setParameter('periodId', $query->periodId);
        }

        $total = (clone $idQb)->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $ids = $idQb->orderBy('e.createdAt', 'DESC')
           ->setFirstResult(($query->page - 1) * $query->perPage)
           ->setMaxResults($query->perPage)
           ->getQuery()
           ->getSingleColumnResult();

        if ($ids === []) {
            return [[], (int) $total];
        }

        $data = $this->entityManager
            ->getRepository(Evaluation::class)
            ->createQueryBuilder('e')
            ->select('e', 'q', 'p', 'ru', 'rup', 'rupp', 'tu', 'tup', 'tupp', 'a', 'aq')
            ->leftJoin('e.questionnaire', 'q')
            ->leftJoin('e.period', 'p')
            ->leftJoin('e.responderUser', 'ru')
            ->leftJoin('ru.personUser', 'rup')
            ->leftJoin('rup.person', 'rupp')
            ->leftJoin('e.targetUser', 'tu')
            ->leftJoin('tu.personUser', 'tup')
            ->leftJoin('tup.person', 'tupp')
            ->leftJoin('e.answers', 'a', 'WITH', 'a.deletedAt IS NULL')
            ->leftJoin('a.question', 'aq')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return [$data, (int) $total];
    }

    public function softDelete(Evaluation $evaluation): void
    {
        $evaluation->setDeletedAt(new \DateTime());
        $this->entityManager->flush();
    }

    public function removeAnswer(Answer $answer): void
    {
        $this->entityManager->remove($answer);
    }

    public function countByResponderUser(int $userId): int
    {
        return (int) $this->entityManager
            ->getRepository(Evaluation::class)
            ->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('IDENTITY(e.responderUser) = :userId')
            ->andWhere('e.deletedAt IS NULL')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByPeriod(): array
    {
        return $this->entityManager
            ->getRepository(Evaluation::class)
            ->createQueryBuilder('e')
            ->select('IDENTITY(e.period) as period_id', 'p.name as period_name', 'COUNT(e.id) as total')
            ->leftJoin('e.period', 'p')
            ->where('e.deletedAt IS NULL')
            ->groupBy('e.period', 'p.name')
            ->orderBy('period_id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
