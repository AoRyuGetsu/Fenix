<?php

declare(strict_types=1);

namespace App\Features\Menu\Repositories;

use App\Features\Menu\Models\Menu;

interface MenuRepositoryInterface
{
    public function findById(int $id): ?Menu;
    public function findByUserId(int $userId): array;
    public function findAll(): array;
    public function save(Menu $menu): void;
    public function softDelete(Menu $menu): void;
}
