<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Router;
use App\Features\User\Application\Controllers\UserController;

require_once __DIR__ . '/../../../../Infrastructure/Auth/middleware.php';

return function (Router $router, Container $container): void {
    $router->addRoute('GET', '/users', middleware($container, function () use ($container) {
        $controller = $container->make(UserController::class);
        return $controller->execute();
    }));

    $router->addRoute('GET', '/users/players', middleware($container, function () use ($container) {
        $controller = $container->make(UserController::class);
        return $controller->players();
    }, ['SUP', 'ADM', 'ENT']));

    $router->addRoute('POST', '/users', middleware($container, function () use ($container) {
        $controller = $container->make(UserController::class);
        return $controller->execute();
    }, ['ADM']));

    $router->addRoute('GET', '/users/{id:\d+}', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(UserController::class);
        return $controller->show((int) $id);
    }));

    $router->addRoute('PUT', '/users/{id:\d+}', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(UserController::class);
        return $controller->update((int) $id);
    }, ['ADM']));

    $router->addRoute('PUT', '/users/{id:\d+}/restore', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(UserController::class);
        return $controller->restore((int) $id);
    }, ['ADM']));

    $router->addRoute('DELETE', '/users/{id:\d+}', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(UserController::class);
        return $controller->delete((int) $id);
    }, ['ADM']));
};
