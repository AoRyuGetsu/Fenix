<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Domain\Repositories;

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
        $section->setDeletedAt(new \DateTime());
        $this->entityManager->flush();
    }

    public function getMaxOrder(int $questionnaireId): int
    {
        $max = $this->entityManager->createQueryBuilder()
            ->select('COALESCE(MAX(s.sectionOrder), 0)')
            ->from(Section::class, 's')
            ->where('s.questionnaire = :questionnaireId')
            ->setParameter('questionnaireId', $questionnaireId)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $max;
    }
}
