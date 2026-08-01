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

$parentescos = [
    ['code' => 'TUTOR_LEGAL', 'description' => 'Tutor legal'],
    ['code' => 'PADRE_MADRE', 'description' => 'Padre/Madre'],
    ['code' => 'HERMANO', 'description' => 'Hermano/Hermana'],
    ['code' => 'TIO_TIA', 'description' => 'Tío/Tía'],
    ['code' => 'FAMILIAR', 'description' => 'Familiar'],
];

foreach ($parentescos as $parentesco) {
    $existing = $conn->executeQuery(
        "SELECT id FROM parentescos WHERE code = ? AND deleted_at IS NULL",
        [$parentesco['code']]
    )->fetchOne();

    if ($existing !== false) {
        echo "• Parentesco '{$parentesco['code']}' ya existe.\n";
        continue;
    }

    $conn->executeStatement(
        "INSERT INTO parentescos (code, description, created_at, updated_at) VALUES (?, ?, ?, ?)",
        [$parentesco['code'], $parentesco['description'], $now, $now]
    );

    echo "✓ Parentesco '{$parentesco['code']}' ({$parentesco['description']}) creado.\n";
}

echo "\nMigración 006 completada.\n";
