<?php

declare(strict_types=1);

$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// Dejar pasar archivos estáticos
if ($path !== null && preg_match('/\.(?:png|jpg|jpeg|gif|css|js|ico|svg|woff2?|ttf|eot)$/', $path)) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';

require __DIR__ . '/index.php';
