<?php

declare(strict_types=1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Env;

Env::load(__DIR__ . '/..');

$env = Env::get('APP_ENV', 'production');
$token = $_GET['token'] ?? '';
$validToken = Env::get('MIGRATION_TOKEN', '');

// ── Reset automático si RESET_DATABASE_ON_MIGRATE=true ──
$resetFromEnv = Env::get('RESET_DATABASE_ON_MIGRATE', 'false') === 'true';
if ($resetFromEnv && !isset($_GET['confirm'])) {
    $_GET['confirm'] = 'reset';
}

if ($env !== 'development' && ($validToken === '' || $token !== $validToken)) {
    http_response_code(403);
    echo '<h1>Acceso denegado</h1>';
    echo '<p>Esta página solo está disponible en entorno de desarrollo o con un token válido.</p>';
    echo '<p>Define <code>MIGRATION_TOKEN</code> en tu <code>.env</code> y accede como <code>/migrate.php?token=tu_token</code>.</p>';
    exit;
}

$migrationsDir = __DIR__ . '/../DB/Migrations';
$files = glob($migrationsDir . '/[0-9]*.php');

if ($files === false || $files === []) {
    echo '<h1>Migraciones</h1>';
    echo '<p>No se encontraron migraciones pendientes.</p>';
    exit;
}

sort($files);

echo '<!DOCTYPE html>';
echo '<html lang="es">';
echo '<head><meta charset="UTF-8"><title>Migraciones</title>';
echo '<style>body{font-family:monospace;max-width:800px;margin:40px auto;padding:0 20px}';
echo '.ok{color:#090}.skip{color:#999}.err{color:#c00}pre{background:#f5f5f5;padding:10px;border-radius:4px}</style>';
echo '</head><body>';
$isReset = ($_GET['confirm'] ?? '') === 'reset';
echo '<h1>Ejecutando migraciones</h1>';
if ($isReset) {
    echo '<p class="reset-warning" style="color:#c00;font-weight:bold;">⚠ Modo RESET — la base de datos se borrará y regenerará desde cero.</p>';
} else {
    echo '<p class="info" style="color:#666;">Modo incremental — las migraciones verifican existencia antes de crear.</p>';
}

foreach ($files as $file) {
    $name = basename($file);

    echo "<h2>{$name}</h2>";

    ob_start();
    require $file;
    $output = ob_get_clean();

    echo '<pre>' . htmlspecialchars($output) . '</pre>';
}

// ── Migraciones de test ──
if (Env::get('RUN_TEST_MIGRATIONS', 'false') === 'true') {
    $testDir = __DIR__ . '/../DB/test';

    if (is_dir($testDir)) {
        $testFiles = glob($testDir . '/[0-9]*.php');

        if ($testFiles !== false && $testFiles !== []) {
            sort($testFiles);

            echo '<h2>--- Migraciones de Test ---</h2>';

            foreach ($testFiles as $file) {
                $name = basename($file);

                echo "<h2>{$name}</h2>";

                ob_start();
                require $file;
                $output = ob_get_clean();

                echo '<pre>' . htmlspecialchars($output) . '</pre>';
            }
        } else {
            echo '<p>No se encontraron migraciones de test.</p>';
        }
    } else {
        echo '<p>Directorio de test no encontrado.</p>';
    }
} else {
    echo '<p>Migraciones de test desactivadas (<code>RUN_TEST_MIGRATIONS</code> no está en <code>true</code>).</p>';
}

echo '<p><strong>Migraciones completadas.</strong></p>';
echo '</body></html>';
