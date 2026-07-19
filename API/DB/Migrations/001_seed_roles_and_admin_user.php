<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Core\Env;
use App\Core\Container;
use App\Features\RolesPermisos\Domain\Interfaces\RoleAccessControlServiceInterface;
use App\Features\RolesPermisos\Domain\Interfaces\RoleRepositoryInterface;
use App\Features\User\Domain\Interfaces\UserServiceInterface;

Env::load(__DIR__ . '/../..');

$container = new Container();

$roleService = $container->get(RoleAccessControlServiceInterface::class);
$roleRepository = $container->get(RoleRepositoryInterface::class);
$userService = $container->get(UserServiceInterface::class);

// ── Backfill o crear roles ──
// Busca roles existentes por nombre para evitar duplicados cuando se migra
// desde una base de datos anterior (sin columna code). Si el rol ya existe
// pero sin código, se actualiza. Si no existe, se crea nuevo.
$existingRoles = $roleRepository->findAll();
$rolesByName = [];
foreach ($existingRoles as $role) {
    $rolesByName[$role->getName()] = $role;
}

$roleDefinitions = [
    'ADM' => 'administrador',
    'ENT' => 'entrenador',
    'JUG' => 'jugador',
    'SUP' => 'supervisor',
    'REP' => 'representante',
];

foreach ($roleDefinitions as $code => $name) {
    if (isset($rolesByName[$name])) {
        $role = $rolesByName[$name];
        if ($role->getCode() === '' || $role->getCode() === null) {
            $role->setCode($code);
            $roleRepository->save($role);
            echo "✓ Rol '{$name}' actualizado con código '{$code}'.\n";
        } else {
            echo "• Rol '{$code}' ({$name}) ya existe.\n";
        }
    } else {
        try {
            $roleService->createRole($code, $name);
            echo "✓ Rol '{$code}' ({$name}) creado.\n";
        } catch (\RuntimeException $e) {
            echo "• Rol '{$code}' ya existe (catch).\n";
        }
    }
}

// ── Usuario administrador ──
$user = $userService->findByEmail('administrador@mail.com');
if ($user === null) {
    $user = $userService->create('administrador@mail.com', 'adminPass1234');
    echo "✓ Usuario 'administrador' creado (ID: {$user->getId()}).\n";
} else {
    echo "• Usuario 'administrador' ya existe.\n";
}

if (!$roleService->hasRole($user->getId(), 'ADM')) {
    $roleService->assignRoleToUser($user->getId(), 'ADM');
    echo "✓ Rol 'ADM' asignado al usuario.\n";
} else {
    echo "• Rol ya estaba asignado.\n";
}

// ── Permisos ──
$permNames = ['read', 'write', 'delete'];
foreach ($permNames as $permName) {
    try {
        $roleService->createPermission($permName);
        echo "✓ Permiso '{$permName}' creado.\n";
    } catch (\RuntimeException $e) {
        echo "• Permiso '{$permName}' ya existe.\n";
    }
}

// ── Asignar permisos a roles ──
$rolePermissions = [
    'ADM' => ['read', 'write', 'delete'],
    'SUP' => ['read'],
    'JUG' => ['read'],
    'ENT' => ['read', 'write', 'delete'],
    'REP' => ['read'],
];

foreach ($rolePermissions as $code => $perms) {
    $role = $roleRepository->findByCode($code);
    if ($role === null) {
        echo "✗ Rol '{$code}' no encontrado.\n";
        continue;
    }

    $existingPermNames = $role->getPermissions()
        ->map(fn($p) => $p->getName())
        ->toArray();

    foreach ($perms as $permName) {
        if (in_array($permName, $existingPermNames, true)) {
            echo "• Permiso '{$permName}' ya asignado a rol '{$code}'.\n";
            continue;
        }
        $roleService->assignPermissionToRole((int) $role->getId(), $permName);
        echo "✓ Permiso '{$permName}' asignado a rol '{$code}'.\n";
    }
}
