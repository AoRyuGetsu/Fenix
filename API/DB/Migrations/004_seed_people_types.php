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

$now = (new \DateTime())->format('Y-m-d H:i:s');

// Los códigos deben coincidir con los role.code para que la
// auto-asignación de roles en UserController::create() funcione.
$types = [
    ['code' => 'ADM', 'description' => 'Administrador'],
    ['code' => 'ENT', 'description' => 'Entrenador'],
    ['code' => 'JUG', 'description' => 'Jugador'],
    ['code' => 'SUP', 'description' => 'Supervisor'],
    ['code' => 'REP', 'description' => 'Representante'],
];

foreach ($types as $type) {
    $existing = $conn->executeQuery(
        "SELECT id FROM people_type WHERE code = ? AND deleted_at IS NULL",
        [$type['code']]
    )->fetchOne();

    if ($existing !== false) {
        echo "• Tipo '{$type['code']}' ya existe.\n";
        continue;
    }

    $conn->executeStatement(
        "INSERT INTO people_type (code, description, created_at, updated_at) VALUES (?, ?, ?, ?)",
        [$type['code'], $type['description'], $now, $now]
    );

    echo "✓ Tipo '{$type['code']}' ({$type['description']}) creado.\n";
}

echo "\nMigración 004 completada.\n";
