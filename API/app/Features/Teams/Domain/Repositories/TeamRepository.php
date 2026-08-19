<?php

declare(strict_types=1);

namespace App\Features\Teams\Domain\Repositories;

use App\Features\Teams\Application\DTO\TeamQueryDto;
use App\Features\Teams\Domain\Entities\Team;
use App\Features\Teams\Domain\Interfaces\TeamRepositoryInterface;
use App\Features\User\Domain\Entities\User;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManager;

class TeamRepository implements TeamRepositoryInterface
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function findPaginated(TeamQueryDto $query): array
    {
        $qb = $this->buildTeamQuery($query);

        $countQb = clone $qb;
        $countQb->select('COUNT(DISTINCT t.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $qb->orderBy('t.id', 'ASC')
            ->setFirstResult(($query->page - 1) * $query->perPage)
            ->setMaxResults($query->perPage);

        return [$qb->getQuery()->getResult(), $total];
    }

    public function findPaginatedByTrainerId(TeamQueryDto $query, int $trainerUserId): array
    {
        $qb = $this->buildTeamQuery($query)
            ->andWhere('t.trainer = :trainerId')
            ->setParameter('trainerId', $trainerUserId);

        $countQb = clone $qb;
        $countQb->select('COUNT(DISTINCT t.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $qb->orderBy('t.id', 'ASC')
            ->setFirstResult(($query->page - 1) * $query->perPage)
            ->setMaxResults($query->perPage);

        return [$qb->getQuery()->getResult(), $total];
    }

    private function buildTeamQuery(TeamQueryDto $query): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->entityManager
            ->getRepository(Team::class)
            ->createQueryBuilder('t')
            ->select('t', 'tr', 'pl')
            ->leftJoin('t.trainer', 'tr')
            ->leftJoin('t.players', 'pl')
            ->where('t.deletedAt IS NULL');

        if ($query->search !== null && $query->search !== '') {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('t.name', ':search'),
                $qb->expr()->like('t.code', ':search'),
            ))->setParameter('search', '%' . $query->search . '%');
        }

        return $qb;
    }

    public function findById(int $id): ?Team
    {
        return $this->entityManager
            ->getRepository(Team::class)
            ->createQueryBuilder('t')
            ->select('t', 'tr', 'pl')
            ->leftJoin('t.trainer', 'tr')
            ->leftJoin('t.players', 'pl')
            ->where('t.id = :id')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByTrainerId(int $trainerUserId): array
    {
        return $this->entityManager
            ->getRepository(Team::class)
            ->createQueryBuilder('t')
            ->select('t', 'pl')
            ->leftJoin('t.players', 'pl')
            ->where('t.trainer = :trainerId')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('trainerId', $trainerUserId)
            ->getQuery()
            ->getResult();
    }

    public function findPlayerIdsByTrainerId(int $trainerUserId): array
    {
        $conn = $this->entityManager->getConnection();
        $result = $conn->executeQuery(
            'SELECT DISTINCT tp.player_user_id
             FROM team_players tp
             JOIN teams t ON t.id = tp.team_id
             WHERE t.trainer_user_id = :trainerId
               AND t.deleted_at IS NULL',
            ['trainerId' => $trainerUserId]
        );
        return array_map('intval', $result->fetchFirstColumn());
    }

    public function save(Team $team): void
    {
        $this->entityManager->persist($team);
        $this->entityManager->flush();
    }

    public function findPlayerIdsByTeamIds(array $teamIds): array
    {
        if ($teamIds === []) {
            return [];
        }

        $conn = $this->entityManager->getConnection();
        $result = $conn->executeQuery(
            'SELECT DISTINCT tp.player_user_id
             FROM team_players tp
             JOIN teams t ON t.id = tp.team_id
             WHERE t.id IN (:teamIds)
               AND t.deleted_at IS NULL',
            ['teamIds' => $teamIds],
            ['teamIds' => ArrayParameterType::INTEGER]
        );
        return array_map('intval', $result->fetchFirstColumn());
    }

    public function findByPlayerId(int $playerUserId): ?Team
    {
        $conn = $this->entityManager->getConnection();
        $teamId = $conn->executeQuery(
            'SELECT team_id FROM team_players WHERE player_user_id = ?',
            [$playerUserId]
        )->fetchOne();

        if ($teamId === false) {
            return null;
        }

        return $this->findById((int) $teamId);
    }

    public function softDelete(Team $team): void
    {
        $team->setDeletedAt(new \DateTime());
        $this->entityManager->flush();
    }
}
