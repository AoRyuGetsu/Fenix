<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Router;
use App\Features\Teams\Application\Controllers\TeamController;

require_once __DIR__ . '/../../../../Infrastructure/Auth/middleware.php';

return function (Router $router, Container $container): void {
    $router->addRoute('GET', '/teams', middleware($container, function () use ($container) {
        $controller = $container->make(TeamController::class);
        return $controller->list();
    }, ['ADM']));

    $router->addRoute('POST', '/teams', middleware($container, function () use ($container) {
        $controller = $container->make(TeamController::class);
        return $controller->create();
    }, ['ADM']));

    $router->addRoute('GET', '/teams/{id:\d+}', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(TeamController::class);
        return $controller->show((int) $id);
    }, ['ADM']));

    $router->addRoute('PUT', '/teams/{id:\d+}', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(TeamController::class);
        return $controller->update((int) $id);
    }, ['ADM']));

    $router->addRoute('DELETE', '/teams/{id:\d+}', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(TeamController::class);
        return $controller->delete((int) $id);
    }, ['ADM']));
};
