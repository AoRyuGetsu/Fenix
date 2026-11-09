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
        'email'    => 'jugador6@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Mateo',
            'first_surname'  => 'Hernández',
            'document_number'      => 'JUG-006',
            'email'          => 'jugador6@mail.com',
        ],
    ],
    [
        'email'    => 'jugador7@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Santiago',
            'first_surname'  => 'Gómez',
            'document_number'      => 'JUG-007',
            'email'          => 'jugador7@mail.com',
        ],
    ],
    [
        'email'    => 'jugador8@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Emiliano',
            'first_surname'  => 'Castro',
            'document_number'      => 'JUG-008',
            'email'          => 'jugador8@mail.com',
        ],
    ],
    [
        'email'    => 'jugadora9@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Valentina',
            'first_surname'  => 'Morales',
            'document_number'      => 'JUG-009',
            'email'          => 'jugadora9@mail.com',
        ],
    ],
    [
        'email'    => 'jugador10@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Iker',
            'first_surname'  => 'Vega',
            'document_number'      => 'JUG-010',
            'email'          => 'jugador10@mail.com',
        ],
    ],
    [
        'email'    => 'jugador11@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Alejandro',
            'first_surname'  => 'Robles',
            'document_number'      => 'JUG-011',
            'email'          => 'jugador11@mail.com',
        ],
    ],
    [
        'email'    => 'jugador12@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Fernanda',
            'first_surname'  => 'Castillo',
            'document_number'      => 'JUG-012',
            'email'          => 'jugador12@mail.com',
        ],
    ],
    [
        'email'    => 'jugador13@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Samuel',
            'first_surname'  => 'Ortega',
            'document_number'      => 'JUG-013',
            'email'          => 'jugador13@mail.com',
        ],
    ],
    [
        'email'    => 'jugador14@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Camila',
            'first_surname'  => 'Mendoza',
            'document_number'      => 'JUG-014',
            'email'          => 'jugador14@mail.com',
        ],
    ],
    [
        'email'    => 'jugador15@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Nicolás',
            'first_surname'  => 'Aguilar',
            'document_number'      => 'JUG-015',
            'email'          => 'jugador15@mail.com',
        ],
    ],
    [
        'email'    => 'jugador16@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Mariana',
            'first_surname'  => 'Serrano',
            'document_number'      => 'JUG-016',
            'email'          => 'jugador16@mail.com',
        ],
    ],
    [
        'email'    => 'jugador17@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Leonardo',
            'first_surname'  => 'Fuentes',
            'document_number'      => 'JUG-017',
            'email'          => 'jugador17@mail.com',
        ],
    ],
    [
        'email'    => 'jugador18@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Daniela',
            'first_surname'  => 'Rojas',
            'document_number'      => 'JUG-018',
            'email'          => 'jugador18@mail.com',
        ],
    ],
    [
        'email'    => 'jugador19@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Gabriel',
            'first_surname'  => 'Salinas',
            'document_number'      => 'JUG-019',
            'email'          => 'jugador19@mail.com',
        ],
    ],
    [
        'email'    => 'jugador20@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Isabella',
            'first_surname'  => 'Cordero',
            'document_number'      => 'JUG-020',
            'email'          => 'jugador20@mail.com',
        ],
    ],
    [
        'email'    => 'jugador21@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Andrés',
            'first_surname'  => 'Peña',
            'document_number'      => 'JUG-021',
            'email'          => 'jugador21@mail.com',
        ],
    ],
    [
        'email'    => 'jugador22@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Sofía',
            'first_surname'  => 'Lozano',
            'document_number'      => 'JUG-022',
            'email'          => 'jugador22@mail.com',
        ],
    ],
    [
        'email'    => 'jugador23@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Sebastián',
            'first_surname'  => 'Delgado',
            'document_number'      => 'JUG-023',
            'email'          => 'jugador23@mail.com',
        ],
    ],
    [
        'email'    => 'jugador24@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Renata',
            'first_surname'  => 'Quiñones',
            'document_number'      => 'JUG-024',
            'email'          => 'jugador24@mail.com',
        ],
    ],
    [
        'email'    => 'jugador25@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Emilio',
            'first_surname'  => 'Navarro',
            'document_number'      => 'JUG-025',
            'email'          => 'jugador25@mail.com',
        ],
    ],
    [
        'email'    => 'jugador26@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Bárbara',
            'first_surname'  => 'Cáceres',
            'document_number'      => 'JUG-026',
            'email'          => 'jugador26@mail.com',
        ],
    ],
    [
        'email'    => 'jugador27@mail.com',
        'password' => 'pass1234',
        'role'     => 'JUG',
        'person'   => [
            'type'           => 'JUG',
            'first_name'     => 'Facundo',
            'first_surname'  => 'Romero',
            'document_number'      => 'JUG-027',
            'email'          => 'jugador27@mail.com',
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

// ── Asignar jugadores a equipos (team_players) ──
$teamAssignments = [
    'SUB12' => ['jugador@mail.com', 'jugador2@mail.com', 'jugador3@mail.com', 'jugador6@mail.com', 'jugador7@mail.com', 'jugador11@mail.com', 'jugador12@mail.com', 'jugador13@mail.com', 'jugador14@mail.com', 'jugador15@mail.com', 'jugador16@mail.com', 'jugador17@mail.com'],
    'SUB18' => ['jugador4@mail.com', 'jugadora5@mail.com', 'jugador8@mail.com', 'jugadora9@mail.com', 'jugador10@mail.com', 'jugador18@mail.com', 'jugador19@mail.com', 'jugador20@mail.com', 'jugador21@mail.com', 'jugador22@mail.com', 'jugador23@mail.com', 'jugador24@mail.com', 'jugador25@mail.com', 'jugador26@mail.com', 'jugador27@mail.com'],
];

foreach ($teamAssignments as $teamCode => $playerEmails) {
    $teamId = $conn->executeQuery(
        "SELECT id FROM teams WHERE code = ? AND deleted_at IS NULL",
        [$teamCode]
    )->fetchOne();

    if ($teamId === false) {
        echo "✗ Equipo '{$teamCode}' no encontrado. No se asignan jugadores.\n";
        continue;
    }
    $teamId = (int) $teamId;

    foreach ($playerEmails as $playerEmail) {
        $playerId = $conn->executeQuery(
            "SELECT id FROM users WHERE email = ? AND deleted_at IS NULL",
            [$playerEmail]
        )->fetchOne();

        if ($playerId === false) {
            echo "✗ Jugador '{$playerEmail}' no encontrado. Se omite asignación al equipo '{$teamCode}'.\n";
            continue;
        }
        $playerId = (int) $playerId;

        $existing = $conn->executeQuery(
            "SELECT 1 FROM team_players WHERE player_user_id = ?",
            [$playerId]
        )->fetchOne();

        if ($existing !== false) {
            echo "• Jugador '{$playerEmail}' ya está asignado a un equipo.\n";
            continue;
        }

        $conn->executeStatement(
            "INSERT INTO team_players (team_id, player_user_id) VALUES (?, ?)",
            [$teamId, $playerId]
        );
        echo "✓ Jugador '{$playerEmail}' asignado al equipo '{$teamCode}'.\n";
    }
}

// ── Asignar representantes a jugadores con parentesco ──
// Los parentescos provienen del catálogo de la tabla `parentescos`
// (seedeado por 006_seed_parentescos.php).
$assignments = [
    ['player_email' => 'jugador@mail.com', 'rep_email' => 'representante@mail.com', 'parentesco' => 'PADRE_MADRE'],
    ['player_email' => 'jugador2@mail.com', 'rep_email' => 'representante2@mail.com', 'parentesco' => 'TUTOR_LEGAL'],
    ['player_email' => 'jugadora5@mail.com', 'rep_email' => 'representante@mail.com', 'parentesco' => 'FAMILIAR'],
];

foreach ($assignments as $assignment) {
    $playerId = $conn->executeQuery(
        "SELECT id FROM users WHERE email = ? AND deleted_at IS NULL",
        [$assignment['player_email']]
    )->fetchOne();

    if ($playerId === false) {
        echo "✗ Jugador '{$assignment['player_email']}' no encontrado. Se omite asignación.\n";
        continue;
    }
    $playerId = (int) $playerId;

    $repId = $conn->executeQuery(
        "SELECT id FROM users WHERE email = ? AND deleted_at IS NULL",
        [$assignment['rep_email']]
    )->fetchOne();

    if ($repId === false) {
        echo "✗ Representante '{$assignment['rep_email']}' no encontrado. Se omite asignación para '{$assignment['player_email']}'.\n";
        continue;
    }
    $repId = (int) $repId;

    $parentescoId = $conn->executeQuery(
        "SELECT id FROM parentescos WHERE code = ? AND deleted_at IS NULL",
        [$assignment['parentesco']]
    )->fetchOne();

    if ($parentescoId === false) {
        echo "✗ Parentesco '{$assignment['parentesco']}' no encontrado en catálogo. Se omite asignación para '{$assignment['player_email']}'.\n";
        continue;
    }
    $parentescoId = (int) $parentescoId;

    $existing = $conn->executeQuery(
        "SELECT player_user_id FROM rep_players WHERE player_user_id = ?",
        [$playerId]
    )->fetchOne();

    if ($existing !== false) {
        echo "• Jugador '{$assignment['player_email']}' ya tiene representante asignado.\n";
        continue;
    }

    $conn->executeStatement(
        "INSERT INTO rep_players (rep_user_id, player_user_id, parentesco_id) VALUES (?, ?, ?)",
        [$repId, $playerId, $parentescoId]
    );
    echo "✓ Representante '{$assignment['rep_email']}' asignado a '{$assignment['player_email']}' (parentesco: {$assignment['parentesco']}).\n";
}

echo "\nMigración de test 001 completada.\n";
