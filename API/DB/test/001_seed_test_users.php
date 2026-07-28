<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Core\Env;
use App\Core\Container;
use App\Features\RolesPermisos\Domain\Interfaces\RoleAccessControlServiceInterface;
use App\Features\User\Domain\Interfaces\UserServiceInterface;

Env::load(__DIR__ . '/../..');

$container = new Container();

$userService = $container->get(UserServiceInterface::class);
$roleService = $container->get(RoleAccessControlServiceInterface::class);

$testUsers = [
    [
        'email'    => 'supervisor@mail.com',
        'password' => 'pass1234',
        'role'     => 'SUP',
        'person'   => [
            'type'           => 'SUP',
            'first_name'     => 'Supervisor',
            'first_surname'  => 'Pérez',
            'document_number'      => 'SUP-001',
            'email'          => 'supervisor@mail.com',
        ],
    ],
    [
        'email'    => 'entrenador@mail.com',
        'password' => 'pass1234',
        'role'     => 'ENT',
        'person'   => [
            'type'           => 'ENT',
            'first_name'     => 'Carlos',
            'first_surname'  => 'López',
            'document_number'      => 'ENT-001',
            'email'          => 'entrenador@mail.com',
        ],
    ],
    [
        'email'    => 'jugador@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Juan',
            'first_surname'  => 'García',
            'document_number'      => 'JUG-001',
            'email'          => 'jugador@mail.com',
        ],
    ],
    [
        'email'    => 'jugador2@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Pedro',
            'first_surname'  => 'Martínez',
            'document_number'      => 'JUG-002',
            'email'          => 'jugador2@mail.com',
        ],
    ],
    [
        'email'    => 'jugador3@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Luis',
            'first_surname'  => 'Fernández',
            'document_number'      => 'JUG-003',
            'email'          => 'jugador3@mail.com',
        ],
    ],
    [
        'email'    => 'jugador4@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Diego',
            'first_surname'  => 'Ramírez',
            'document_number'      => 'JUG-004',
            'email'          => 'jugador4@mail.com',
        ],
    ],
    [
        'email'    => 'jugadora5@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Ana',
            'first_surname'  => 'Torres',
            'document_number'      => 'JUG-005',
            'email'          => 'jugadora5@mail.com',
        ],
    ],
    [
        'email'    => 'representante@mail.com',
        'password' => 'pass1234',
        'role'     => 'REP',
        'person'   => [
            'type'           => 'REP',
            'first_name'     => 'María',
            'first_surname'  => 'Rodríguez',
            'document_number'      => 'REP-001',
            'email'          => 'representante@mail.com',
        ],
    ],
    [
        'email'    => 'entrenador2@mail.com',
        'password' => 'pass1234',
        'role'     => 'ENT',
        'person'   => [
            'type'           => 'ENT',
            'first_name'     => 'Roberto',
            'first_surname'  => 'Silva',
            'document_number'      => 'ENT-002',
            'email'          => 'entrenador2@mail.com',
        ],
    ],
    [
        'email'    => 'representante2@mail.com',
        'password' => 'pass1234',
        'role'     => 'REP',
        'person'   => [
            'type'           => 'REP',
            'first_name'     => 'Laura',
            'first_surname'  => 'Díaz',
            'document_number'      => 'REP-002',
            'email'          => 'representante2@mail.com',
        ],
    ],
];

foreach ($testUsers as $data) {
    $existing = $userService->findByEmail($data['email']);

    if ($existing !== null) {
        $userId = $existing->getId();
        echo "• Usuario '{$data['email']}' ya existe (ID: {$userId}).\n";

        // Asegurar que el rol esté asignado aunque el usuario ya exista
        if (!$roleService->hasRole($userId, $data['role'])) {
            try {
                $roleService->assignRoleToUser($userId, $data['role']);
                echo "✓ Rol '{$data['role']}' asignado a '{$data['email']}' (estaba pendiente).\n";
            } catch (\RuntimeException $e) {
                echo "✗ Error al asignar rol '{$data['role']}' a '{$data['email']}': {$e->getMessage()}\n";
            }
        }
        continue;
    }

    try {
        $user = $userService->create($data['email'], $data['password'], $data['person']);
        echo "✓ Usuario '{$data['email']}' creado (ID: {$user->getId()}).\n";
    } catch (\RuntimeException $e) {
        echo "✗ Error al crear '{$data['email']}': {$e->getMessage()}\n";
        continue;
    }

    try {
        $roleService->assignRoleToUser($user->getId(), $data['role']);
        echo "✓ Rol '{$data['role']}' asignado a '{$data['email']}'.\n";
    } catch (\RuntimeException $e) {
        echo "✗ Error al asignar rol '{$data['role']}' a '{$data['email']}': {$e->getMessage()}\n";
    }
}

// ── Crear equipos ──
$entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
$conn = $entityManager->getConnection();
$now = (new \DateTime())->format('Y-m-d H:i:s');

$teams = [
    ['name' => 'Sub 12', 'code' => 'SUB12', 'trainer_email' => 'entrenador@mail.com'],
    ['name' => 'Sub 18', 'code' => 'SUB18', 'trainer_email' => 'entrenador2@mail.com'],
];

foreach ($teams as $team) {
    $existing = $conn->executeQuery(
        "SELECT id FROM teams WHERE code = ? AND deleted_at IS NULL",
        [$team['code']]
    )->fetchOne();

    if ($existing !== false) {
        echo "• Equipo '{$team['name']}' ya existe (ID: {$existing}).\n";
        continue;
    }

    $trainerId = $conn->executeQuery(
        "SELECT id FROM users WHERE email = ? AND deleted_at IS NULL",
        [$team['trainer_email']]
    )->fetchOne();

    if ($trainerId === false) {
        echo "✗ Entrenador '{$team['trainer_email']}' no encontrado. No se creó el equipo '{$team['name']}'.\n";
        continue;
    }

    $conn->executeStatement(
        "INSERT INTO teams (name, code, description, trainer_user_id, created_by, updated_by, created_at, updated_at)
         VALUES (?, ?, NULL, ?, ?, ?, ?, ?)",
        [$team['name'], $team['code'], $trainerId, $trainerId, $trainerId, $now, $now]
    );
    echo "✓ Equipo '{$team['name']}' ({$team['code']}) creado.\n";
}

echo "\nMigración de test 001 completada.\n";
