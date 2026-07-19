<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Core\Env;
use App\Core\Container;
use Doctrine\ORM\EntityManager;

$confirm = $_GET['confirm'] ?? '';
$cliReset = in_array('--reset', $_SERVER['argv'] ?? [], true);

if ($confirm !== 'reset' && !$cliReset) {
    echo "• Saltando — usar ?confirm=reset (web) o --reset (CLI) para ejecutar.\n";
    return;
}

Env::load(__DIR__ . '/../..');

$container = new Container();
$entityManager = $container->get(EntityManager::class);
$conn = $entityManager->getConnection();

echo "=== Restaurando estructura de la base de datos ===\n";

$schemaManager = $conn->createSchemaManager();
$tables = $schemaManager->listTableNames();

if (empty($tables)) {
    echo "• No hay tablas que eliminar.\n";
} else {
    $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
    foreach ($tables as $table) {
        $conn->executeStatement("DROP TABLE IF EXISTS `$table`");
        echo "✗ Eliminada: `$table`\n";
    }
    $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
}

$sqlFile = __DIR__ . '/../SQL/002_create_structure.sql';
if (!file_exists($sqlFile)) {
    echo "✗ Error: No se encuentra $sqlFile\n";
    return;
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    echo "✗ Error al leer $sqlFile\n";
    return;
}

$statements = array_filter(
    array_map(fn($s) => preg_replace('/^--.*\n?/m', '', trim($s)), explode(';', $sql)),
    fn($s) => !empty($s) && !str_starts_with($s, '--')
);

foreach ($statements as $stmt) {
    try {
        $conn->executeStatement($stmt);
        $preview = mb_substr($stmt, 0, 70);
        echo "✓ $preview...\n";
    } catch (\Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
}

echo "✓ Estructura restaurada exitosamente.\n";
