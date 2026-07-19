<?php

declare(strict_types=1);

namespace App\Infrastructure\Menu\Domain\Repositories;
use App\Infrastructure\Menu\Domain\Interfaces\MenuRepositoryInterface;

use App\Infrastructure\Menu\Domain\Entities\Menu;
use Doctrine\ORM\EntityManager;

class MenuRepository implements MenuRepositoryInterface
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function findById(int $id): ?Menu
    {
        return $this->entityManager->find(Menu::class, $id);
    }

    public function findByUserId(int $userId): array
    {
        $query = $this->entityManager->createQuery(
            'SELECT m FROM ' . Menu::class . ' m '
            . 'JOIN m.roles r '
            . 'JOIN r.users u '
            . 'WHERE u.id = :userId '
            . 'AND m.isActive = :active '
            . 'AND m.deletedAt IS NULL '
            . 'ORDER BY m.menuOrder, m.id'
        );
        $query->setParameter('userId', $userId);
        $query->setParameter('active', true);

        return $query->getResult();
    }

    public function findAll(): array
    {
        return $this->entityManager
            ->getRepository(Menu::class)
            ->createQueryBuilder('m')
            ->where('m.deletedAt IS NULL')
            ->andWhere('m.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('m.menuOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Menu $menu): void
    {
        $this->entityManager->persist($menu);
        $this->entityManager->flush();
    }

    public function softDelete(Menu $menu): void
    {
        $menu->setDeletedAt(new \DateTime());
        $this->entityManager->flush();
    }
}
