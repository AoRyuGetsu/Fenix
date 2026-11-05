<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Domain\Repositories;

use App\Features\Questionnaire\Application\DTO\SectionQuery;
use App\Features\Questionnaire\Domain\Entities\Section;
use App\Features\Questionnaire\Domain\Interfaces\SectionRepositoryInterface;
use Doctrine\ORM\EntityManager;

class SectionRepository implements SectionRepositoryInterface
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function findById(int $id): ?Section
    {
        return $this->entityManager->getRepository(Section::class)->find($id);
    }

    public function findByQuestionnaire(int $questionnaireId): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('s')
            ->from(Section::class, 's')
            ->where('s.questionnaire = :questionnaireId')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('questionnaireId', $questionnaireId)
            ->orderBy('s.sectionOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Section $section): void
    {
        $this->entityManager->persist($section);
        $this->entityManager->flush();
    }

    public function softDelete(Section $section): void
    {
        foreach ($section->getQuestions() as $question) {
            foreach ($question->getOptions() as $option) {
                $this->entityManager->remove($option);
            }
            $question->setDeletedAt(new \DateTime());
        }
        $section->setDeletedAt(new \DateTime());
        $this->entityManager->flush();
    }

    public function findPaginated(SectionQuery $query): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('s')
            ->from(Section::class, 's')
            ->where('s.questionnaire = :questionnaireId')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('questionnaireId', $query->questionnaireId)
            ->orderBy('s.sectionOrder', 'ASC');

        $total = (clone $qb)->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $qb->setFirstResult(($query->page - 1) * $query->perPage)
            ->setMaxResults($query->perPage)
            ->getQuery()
            ->getResult();

        return [$items, (int) $total];
    }

    public function getMaxOrder(int $questionnaireId): int
    {
        $max = $this->entityManager->createQueryBuilder()
            ->select('COALESCE(MAX(s.sectionOrder), 0)')
            ->from(Section::class, 's')
            ->where('s.questionnaire = :questionnaireId')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('questionnaireId', $questionnaireId)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $max;
    }
}
