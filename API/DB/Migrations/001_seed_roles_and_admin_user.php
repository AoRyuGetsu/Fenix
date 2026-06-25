<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Core\Env;
use App\Core\Container;
use App\Features\Auth\Contracts\RoleAccessControlServiceInterface;
use App\Features\Auth\Repositories\RoleRepositoryInterface;
use App\Features\User\Contracts\UserServiceInterface;

Env::load(__DIR__ . '/../..');

$container = new Container();

$roleService = $container->get(RoleAccessControlServiceInterface::class);
$userService = $container->get(UserServiceInterface::class);

$roles = ['administrador', 'entrenador', 'jugador', 'supervisor', 'representante'];
foreach ($roles as $roleName) {
    try {
        $roleService->createRole($roleName);
        echo "✓ Rol '{$roleName}' creado.\n";
    } catch (\RuntimeException $e) {
        echo "• Rol '{$roleName}' ya existe.\n";
    }
}

$user = $userService->findByEmail('administrador@mail.com');
if ($user === null) {
    $user = $userService->create('administrador@mail.com', 'adminPass1234');
    echo "✓ Usuario 'administrador' creado (ID: {$user->getId()}).\n";
} else {
    echo "• Usuario 'administrador' ya existe.\n";
}

if (!$roleService->hasRole($user->getId(), 'administrador')) {
    $roleService->assignRoleToUser($user->getId(), 'administrador');
    echo "✓ Rol 'administrador' asignado al usuario.\n";
} else {
    echo "• Rol ya estaba asignado.\n";
}

$roleRepository = $container->get(RoleRepositoryInterface::class);

$permNames = ['read', 'write', 'delete'];
foreach ($permNames as $permName) {
    try {
        $roleService->createPermission($permName);
        echo "✓ Permiso '{$permName}' creado.\n";
    } catch (\RuntimeException $e) {
        echo "• Permiso '{$permName}' ya existe.\n";
    }
}

$rolePermissions = [
    'administrador'  => ['read', 'write', 'delete'],
    'supervisor'     => ['read'],
    'jugador'        => ['read'],
    'entrenador'     => ['read', 'write', 'delete'],
    'representante'  => ['read'],
];

foreach ($rolePermissions as $roleName => $perms) {
    $role = $roleRepository->findByName($roleName);
    if ($role === null) {
        echo "✗ Rol '{$roleName}' no encontrado.\n";
        continue;
    }
    foreach ($perms as $permName) {
        $roleService->assignPermissionToRole((int) $role->getId(), $permName);
        echo "✓ Permiso '{$permName}' asignado a rol '{$roleName}'.\n";
    }
}
