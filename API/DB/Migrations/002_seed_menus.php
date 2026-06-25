<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Core\Env;
use App\Core\Container;
use Doctrine\ORM\EntityManager;

Env::load(__DIR__ . '/../..');

$container = new Container();
$entityManager = $container->get(EntityManager::class);
$conn = $entityManager->getConnection();

$adminUser = $conn->executeQuery(
    "SELECT id FROM users WHERE email = 'administrador' AND deleted_at IS NULL"
)->fetchAssociative();

if (!$adminUser) {
    echo "✗ Usuario 'administrador' no encontrado. Ejecuta primero la migración 001.\n";
    exit(1);
}
$adminUserId = (int) $adminUser['id'];

$roles = $conn->executeQuery(
    "SELECT id, name FROM roles WHERE deleted_at IS NULL"
)->fetchAllAssociative();
$roleIds = [];
foreach ($roles as $role) {
    $roleIds[$role['name']] = (int) $role['id'];
}

$menuTree = [
    ['label' => 'Configuración', 'icon' => 'settings', 'route' => '/configuracion',     'order' => 1],
    ['label' => 'Roles',         'icon' => 'shield',   'route' => '/configuracion/roles', 'order' => 1, 'parent' => 'Configuración'],
    ['label' => 'Permisos',      'icon' => 'lock',     'route' => '/configuracion/permisos', 'order' => 2, 'parent' => 'Configuración'],
    ['label' => 'Usuarios',      'icon' => 'people',   'route' => '/configuracion/usuarios', 'order' => 3, 'parent' => 'Configuración'],
];

$now = (new \DateTime())->format('Y-m-d H:i:s');
$menuIds = [];

foreach ($menuTree as $menu) {
    if (isset($menu['parent'])) {
        $existingId = $conn->executeQuery(
            "SELECT id FROM menus WHERE label = ? AND parent_id = ? AND deleted_at IS NULL",
            [$menu['label'], $menuIds[$menu['parent']]]
        )->fetchOne();
    } else {
        $existingId = $conn->executeQuery(
            "SELECT id FROM menus WHERE label = ? AND parent_id IS NULL AND deleted_at IS NULL",
            [$menu['label']]
        )->fetchOne();
    }

    if ($existingId !== false) {
        $menuIds[$menu['label']] = (int) $existingId;
        echo "• Menú '{$menu['label']}' ya existe.\n";
        continue;
    }

    if (isset($menu['parent'])) {
        $conn->executeStatement(
            "INSERT INTO menus (parent_id, label, icon, route, menu_order, is_active, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?)",
            [$menuIds[$menu['parent']], $menu['label'], $menu['icon'], $menu['route'], $menu['order'], $adminUserId, $now, $now]
        );
    } else {
        $conn->executeStatement(
            "INSERT INTO menus (label, icon, route, menu_order, is_active, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, 1, ?, ?, ?)",
            [$menu['label'], $menu['icon'], $menu['route'], $menu['order'], $adminUserId, $now, $now]
        );
    }

    $menuIds[$menu['label']] = (int) $conn->lastInsertId();
    echo "✓ Menú '{$menu['label']}' creado (ID: {$menuIds[$menu['label']]}).\n";
}

$assignments = [
    'administrador' => ['Configuración', 'Roles', 'Permisos', 'Usuarios'],
    'supervisor'    => ['Configuración', 'Roles', 'Usuarios'],
];

foreach ($assignments as $roleName => $menuLabels) {
    if (!isset($roleIds[$roleName])) {
        echo "• Rol '{$roleName}' no existe, se omite.\n";
        continue;
    }
    $rid = $roleIds[$roleName];
    $count = 0;
    foreach ($menuLabels as $label) {
        if (!isset($menuIds[$label])) {
            continue;
        }
        $mid = $menuIds[$label];
        $exists = $conn->executeQuery(
            "SELECT 1 FROM menu_role WHERE menu_id = ? AND role_id = ?",
            [$mid, $rid]
        )->fetchOne();
        if ($exists === false) {
            $conn->executeStatement(
                "INSERT INTO menu_role (menu_id, role_id) VALUES (?, ?)",
                [$mid, $rid]
            );
            $count++;
        }
    }
    if ($count > 0) {
        echo "✓ {$count} menú(es) asignado(s) a '{$roleName}'.\n";
    } else {
        echo "• Menús ya estaban asignados a '{$roleName}'.\n";
    }
}
