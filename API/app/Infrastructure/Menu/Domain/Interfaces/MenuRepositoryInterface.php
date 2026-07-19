<?php

declare(strict_types=1);

namespace App\Infrastructure\Menu\Domain\Interfaces;

use App\Infrastructure\Menu\Domain\Entities\Menu;

interface MenuRepositoryInterface
{
    public function findById(int $id): ?Menu;
    public function findByUserId(int $userId): array;
    public function findAll(): array;
    public function save(Menu $menu): void;
    public function softDelete(Menu $menu): void;
}
