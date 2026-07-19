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

// ── Incremental schema: crear tabla periods si no existe ──
$schemaManager = $conn->createSchemaManager();
$tables = $schemaManager->listTableNames();

if (!in_array('periods', $tables, true)) {
    $conn->executeStatement("
        CREATE TABLE periods (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            created_by BIGINT UNSIGNED DEFAULT NULL,
            updated_by BIGINT UNSIGNED DEFAULT NULL,
            deleted_by BIGINT UNSIGNED DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            deleted_at DATETIME DEFAULT NULL,
            CONSTRAINT fk_periods_created_by FOREIGN KEY (created_by) REFERENCES users(id),
            CONSTRAINT fk_periods_updated_by FOREIGN KEY (updated_by) REFERENCES users(id),
            CONSTRAINT fk_periods_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id)
        ) ENGINE=InnoDB
    ");
    echo "✓ Tabla 'periods' creada.\n";
}

// ── Incremental schema: agregar period_id a evaluations si no existe ──
if (in_array('evaluations', $tables, true)) {
    $columns = $schemaManager->listTableColumns('evaluations');
    $colNames = array_map(fn($c) => $c->getName(), $columns);
    if (!in_array('period_id', $colNames, true)) {
        $conn->executeStatement("
            ALTER TABLE evaluations
            ADD COLUMN period_id BIGINT UNSIGNED NOT NULL AFTER target_user_id,
            ADD CONSTRAINT fk_evals_period FOREIGN KEY (period_id) REFERENCES periods(id),
            ADD CONSTRAINT uq_eval_period UNIQUE (questionnaire_id, responder_user_id, target_user_id, period_id)
        ");
        echo "✓ Columna 'period_id' agregada a evaluations.\n";
    }
}

$periods = [
    ['name' => 'Ene-Mar 2025', 'start_date' => '2025-01-01', 'end_date' => '2025-03-31'],
    ['name' => 'Abr-Jun 2025', 'start_date' => '2025-04-01', 'end_date' => '2025-06-30'],
    ['name' => 'Jul-Sep 2025', 'start_date' => '2025-07-01', 'end_date' => '2025-09-30'],
    ['name' => 'Oct-Dic 2025', 'start_date' => '2025-10-01', 'end_date' => '2025-12-31'],
    ['name' => 'Ene-Mar 2026', 'start_date' => '2026-01-01', 'end_date' => '2026-03-31'],
    ['name' => 'Abr-Jun 2026', 'start_date' => '2026-04-01', 'end_date' => '2026-06-30'],
];

foreach ($periods as $period) {
    $existing = $conn->executeQuery(
        "SELECT id FROM periods WHERE name = ? AND deleted_at IS NULL",
        [$period['name']]
    )->fetchOne();

    if ($existing !== false) {
        echo "• Periodo '{$period['name']}' ya existe (ID: {$existing}).\n";
        continue;
    }

    $conn->executeStatement(
        "INSERT INTO periods (name, start_date, end_date, created_at, updated_at) VALUES (?, ?, ?, ?, ?)",
        [$period['name'], $period['start_date'], $period['end_date'], $now, $now]
    );

    echo "✓ Periodo '{$period['name']}' ({$period['start_date']} → {$period['end_date']}) creado.\n";
}

echo "\nMigración 005 completada.\n";
