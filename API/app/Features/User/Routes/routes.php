<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Router;
use App\Features\User\Controllers\UserController;

return function (Router $router, Container $container): void {
    $router->addRoute('GET', '/users', function () use ($container) {
        $controller = $container->make(UserController::class);
        return $controller->execute();
    });

    $router->addRoute('POST', '/users', function () use ($container) {
        $controller = $container->make(UserController::class);
        return $controller->execute();
    });

    $router->addRoute('GET', '/users/{id}', function (string $id) use ($container) {
        $controller = $container->make(UserController::class);
        return $controller->show((int) $id);
    });

    $router->addRoute('PUT', '/users/{id}', function (string $id) use ($container) {
        $controller = $container->make(UserController::class);
        return $controller->update((int) $id);
    });

    $router->addRoute('DELETE', '/users/{id}', function (string $id) use ($container) {
        $controller = $container->make(UserController::class);
        return $controller->delete((int) $id);
    });
};
