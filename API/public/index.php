<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Env;
use App\Core\Kernel;
use App\Utils\ErrorHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

Env::load(__DIR__ . '/..');

$debug = (bool) Env::get('APP_DEBUG', false);
ErrorHandler::register($debug);

$request = Request::createFromGlobals();
$kernel = new Kernel();
$response = $kernel->handle($request);

$response->send();
