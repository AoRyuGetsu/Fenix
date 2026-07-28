<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Router;
use App\Features\PlayerSpecification\Application\Controllers\PlayerSpecificationController;

require_once __DIR__ . '/../../../../Infrastructure/Auth/middleware.php';

return function (Router $router, Container $container): void {
    $router->addRoute('GET', '/player-specifications', middleware($container, function () use ($container) {
        $controller = $container->make(PlayerSpecificationController::class);
        return $controller->list();
    }, ['ADM']));

    $router->addRoute('GET', '/player-specifications/{id:\d+}', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(PlayerSpecificationController::class);
        return $controller->show((int) $id);
    }, ['ADM', 'ENT', 'JUG']));

    $router->addRoute('POST', '/player-specifications', middleware($container, function () use ($container) {
        $controller = $container->make(PlayerSpecificationController::class);
        return $controller->create();
    }, ['ADM']));

    $router->addRoute('PUT', '/player-specifications/{id:\d+}', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(PlayerSpecificationController::class);
        return $controller->update((int) $id);
    }, ['ADM']));

    $router->addRoute('DELETE', '/player-specifications/{id:\d+}', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(PlayerSpecificationController::class);
        return $controller->delete((int) $id);
    }, ['ADM']));
};
