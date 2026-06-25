<?php

declare(strict_types=1);

namespace App\Features\Menu\Repositories;

use App\Features\Menu\Models\Menu;
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
            ->findBy([], ['menuOrder' => 'ASC']);
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
