<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Env;
use App\Core\Kernel;
use App\Utils\ErrorHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

Env::load(__DIR__ . '/..');

$debug = (bool) Env::get('APP_DEBUG', false);
ErrorHandler::register($debug);

try {
    $request = Request::createFromGlobals();
    $kernel = new Kernel();
    $response = $kernel->handle($request);
} catch (\Throwable $e) {
    $response = new JsonResponse(['error' => 'Internal server error'], 500);
}

$response->headers->set('Access-Control-Allow-Origin', '*');
$response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
$response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');

$response->send();
