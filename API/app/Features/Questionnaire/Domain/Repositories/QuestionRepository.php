<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Domain\Repositories;

use App\Features\Questionnaire\Application\DTO\QuestionQuery;
use App\Features\Questionnaire\Domain\Interfaces\QuestionRepositoryInterface;
use App\Features\Questionnaire\Domain\Entities\Question;
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

    public function softDelete(Question $question): void
    {
        foreach ($question->getOptions() as $option) {
            $this->entityManager->remove($option);
        }

        $question->setDeletedAt(new \DateTime());
        $this->entityManager->flush();
    }
}
