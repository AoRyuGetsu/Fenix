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
    "SELECT id FROM users WHERE email = 'administrador@mail.com' AND deleted_at IS NULL"
)->fetchAssociative();

if (!$adminUser) {
    echo "✗ Usuario 'administrador' no encontrado. Ejecuta primero la migración 001.\n";
    exit(1);
}
$adminUserId = (int) $adminUser['id'];

$roles = $conn->executeQuery(
    "SELECT id, code FROM roles WHERE deleted_at IS NULL"
)->fetchAllAssociative();
$roleIds = [];
$adminRoleId = null;
foreach ($roles as $role) {
    $id = (int) $role['id'];
    $roleIds[] = $id;
    if ($role['code'] === 'ADM') {
        $adminRoleId = $id;
    }
}

if ($adminRoleId === null) {
    echo "✗ Rol 'ADM' no encontrado.\n";
    exit(1);
}

$now = (new \DateTime())->format('Y-m-d H:i:s');

/**
 * Crea o recupera un menú por label y parent_id.
 * Retorna el ID del menú.
 */
function ensureMenu(array &$menuIds, $conn, string $label, ?int $parentId, string $icon, string $route, int $order, int $adminUserId, string $now): int
{
    if ($parentId !== null) {
        $existingId = $conn->executeQuery(
            "SELECT id FROM menus WHERE label = ? AND parent_id = ? AND deleted_at IS NULL",
            [$label, $parentId]
        )->fetchOne();
    } else {
        $existingId = $conn->executeQuery(
            "SELECT id FROM menus WHERE label = ? AND parent_id IS NULL AND deleted_at IS NULL",
            [$label]
        )->fetchOne();
    }

    if ($existingId !== false) {
        $menuIds[$label] = (int) $existingId;
        return (int) $existingId;
    }

    if ($parentId !== null) {
        $conn->executeStatement(
            "INSERT INTO menus (parent_id, label, icon, route, menu_order, is_active, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?)",
            [$parentId, $label, $icon, $route, $order, $adminUserId, $now, $now]
        );
    } else {
        $conn->executeStatement(
            "INSERT INTO menus (label, icon, route, menu_order, is_active, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, 1, ?, ?, ?)",
            [$label, $icon, $route, $order, $adminUserId, $now, $now]
        );
    }

    $menuIds[$label] = (int) $conn->lastInsertId();
    return $menuIds[$label];
}

/**
 * Asigna un menú a un rol si no está ya asignado.
 */
function assignMenu($conn, int $menuId, int $roleId, string $roleName): bool
{
    $exists = $conn->executeQuery(
        "SELECT 1 FROM menu_role WHERE menu_id = ? AND role_id = ?",
        [$menuId, $roleId]
    )->fetchOne();

    if ($exists === false) {
        $conn->executeStatement(
            "INSERT INTO menu_role (menu_id, role_id) VALUES (?, ?)",
            [$menuId, $roleId]
        );
        return true;
    }
    return false;
}

$menuIds = [];

// ── Top-level: Evaluaciones (order 2, todos los roles) ──
$evaluacionesId = ensureMenu($menuIds, $conn, 'Evaluaciones', null, 'clipboard-check', '/evaluaciones', 2, $adminUserId, $now);
echo "✓ Menú 'Evaluaciones' creado/verificado (ID: {$evaluacionesId}).\n";

// ── Top-level: Configuración (order 1, solo admin) ──
$configId = ensureMenu($menuIds, $conn, 'Configuración', null, 'settings', '/configuracion', 1, $adminUserId, $now);
echo "✓ Menú 'Configuración' creado/verificado (ID: {$configId}).\n";

// ── Hijos de Configuración (todos solo admin) ──
$rolesId = ensureMenu($menuIds, $conn, 'Roles', $configId, 'shield', '/configuracion/roles-permisos', 1, $adminUserId, $now);
echo "✓ Menú 'Roles' creado/verificado (ID: {$rolesId}).\n";

$usuariosId = ensureMenu($menuIds, $conn, 'Usuarios', $configId, 'people', '/configuracion/usuarios', 2, $adminUserId, $now);
echo "✓ Menú 'Usuarios' creado/verificado (ID: {$usuariosId}).\n";

$cuestionariosId = ensureMenu($menuIds, $conn, 'Cuestionarios', $configId, 'questionnaire', '/configuracion/cuestionarios', 3, $adminUserId, $now);
echo "✓ Menú 'Cuestionarios' creado/verificado (ID: {$cuestionariosId}).\n";

$periodosId = ensureMenu($menuIds, $conn, 'Periodos', $configId, 'calendar-date', '/configuracion/periodos', 4, $adminUserId, $now);
echo "✓ Menú 'Periodos' creado/verificado (ID: {$periodosId}).\n";

// ── Asignaciones por rol ──

// Evaluaciones: todos los roles
foreach ($roleIds as $rid) {
    if (assignMenu($conn, $evaluacionesId, $rid, "role-{$rid}")) {
        echo "✓ Evaluaciones asignado a rol ID {$rid}.\n";
    }
}

// Configuración y sus hijos: solo administrador
$adminMenus = [$configId, $rolesId, $usuariosId, $cuestionariosId, $periodosId];
foreach ($adminMenus as $mid) {
    if (assignMenu($conn, $mid, $adminRoleId, 'ADM')) {
        $label = array_search($mid, $menuIds) ?: "Menu #{$mid}";
        echo "✓ {$label} asignado a 'ADM'.\n";
    }
}

echo "\nMigración 002 completada.\n";
