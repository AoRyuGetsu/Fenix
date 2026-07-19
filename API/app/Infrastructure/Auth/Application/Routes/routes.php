<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Router;
use App\Infrastructure\Auth\Application\Controllers\AuthController;

require_once __DIR__ . '/../../middleware.php';

return function (Router $router, Container $container): void {
    $router->addRoute('POST', '/auth/login', function () use ($container) {
        $controller = $container->make(AuthController::class);
        return $controller->login();
    });

    $router->addRoute('POST', '/auth/refresh', function () use ($container) {
        $controller = $container->make(AuthController::class);
        return $controller->refresh();
    });

    $router->addRoute('GET', '/auth/me', middleware($container, function () use ($container) {
        $controller = $container->make(AuthController::class);
        return $controller->me();
    }));
};
