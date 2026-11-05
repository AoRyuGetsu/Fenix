<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Domain\Repositories;

use App\Features\Questionnaire\Application\DTO\QuestionQuery;
use App\Features\Questionnaire\Domain\Interfaces\QuestionRepositoryInterface;
use App\Features\Questionnaire\Domain\Entities\Question;
use App\Features\Questionnaire\Domain\Entities\QuestionOption;
use Doctrine\ORM\EntityManager;

class QuestionRepository implements QuestionRepositoryInterface
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function save(Question $question): void
    {
        $this->entityManager->persist($question);
        $this->entityManager->flush();
    }

    public function findById(int $id): ?Question
    {
        return $this->entityManager
            ->getRepository(Question::class)
            ->createQueryBuilder('q')
            ->where('q.id = :id')
            ->andWhere('q.deletedAt IS NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findBySection(int $sectionId): array
    {
        return $this->entityManager
            ->getRepository(Question::class)
            ->createQueryBuilder('q')
            ->where('q.section = :sectionId')
            ->andWhere('q.deletedAt IS NULL')
            ->setParameter('sectionId', $sectionId)
            ->orderBy('q.questionOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPaginated(QuestionQuery $query): array
    {
        $qb = $this->entityManager
            ->getRepository(Question::class)
            ->createQueryBuilder('q')
            ->where('q.section = :sectionId')
            ->andWhere('q.deletedAt IS NULL')
            ->setParameter('sectionId', $query->sectionId)
            ->orderBy('q.questionOrder', 'ASC');

        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(q.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $qb->setFirstResult(($query->page - 1) * $query->perPage)
            ->setMaxResults($query->perPage)
            ->getQuery()
            ->getResult();

        return [$items, $total];
    }

    public function getMaxOrder(int $sectionId): int
    {
        $max = $this->entityManager
            ->getRepository(Question::class)
            ->createQueryBuilder('q')
            ->select('COALESCE(MAX(q.questionOrder), 0)')
            ->where('q.section = :sectionId')
            ->andWhere('q.deletedAt IS NULL')
            ->setParameter('sectionId', $sectionId)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $max;
    }

    public function findAdjacent(int $sectionId, int $order, string $direction): ?Question
    {
        $qb = $this->entityManager
            ->getRepository(Question::class)
            ->createQueryBuilder('q')
            ->where('q.section = :sectionId')
            ->andWhere('q.deletedAt IS NULL')
            ->setParameter('sectionId', $sectionId);

        if ($direction === 'up') {
            $qb->andWhere('q.questionOrder < :order')
                ->orderBy('q.questionOrder', 'DESC');
        } else {
            $qb->andWhere('q.questionOrder > :order')
                ->orderBy('q.questionOrder', 'ASC');
        }

        return $qb->setParameter('order', $order)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function softDelete(Question $question): void
    {
        foreach ($question->getOptions() as $option) {
            $option->setDeletedAt(new \DateTime());
        }

        $question->setDeletedAt(new \DateTime());
        $this->entityManager->flush();
    }

    public function findOptionsIncludingDeleted(int $questionId): array
    {
        $filters = $this->entityManager->getFilters();
        $wasEnabled = $filters->isEnabled('soft-delete');
        if ($wasEnabled) {
            $filters->disable('soft-delete');
        }

        try {
            return $this->entityManager
                ->getRepository(QuestionOption::class)
                ->createQueryBuilder('o')
                ->where('o.question = :questionId')
                ->setParameter('questionId', $questionId)
                ->orderBy('o.optionOrder', 'ASC')
                ->getQuery()
                ->getResult();
        } finally {
            if ($wasEnabled) {
                $filters->enable('soft-delete');
            }
        }
    }
}
