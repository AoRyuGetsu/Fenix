<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Router;
use App\Features\People\Controllers\PersonController;

return function (Router $router, Container $container): void {
    $router->addRoute('GET', '/people', function () use ($container) {
        $controller = $container->make(PersonController::class);
        return $controller->execute();
    });

    $router->addRoute('POST', '/people', function () use ($container) {
        $controller = $container->make(PersonController::class);
        return $controller->execute();
    });

    $router->addRoute('GET', '/people/{id}', function (string $id) use ($container) {
        $controller = $container->make(PersonController::class);
        return $controller->show((int) $id);
    });

    $router->addRoute('PUT', '/people/{id}', function (string $id) use ($container) {
        $controller = $container->make(PersonController::class);
        return $controller->update((int) $id);
    });

    $router->addRoute('DELETE', '/people/{id}', function (string $id) use ($container) {
        $controller = $container->make(PersonController::class);
        return $controller->delete((int) $id);
    });
};
