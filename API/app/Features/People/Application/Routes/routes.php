<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Router;
use App\Features\People\Application\Controllers\PeopleTypeController;
use App\Features\People\Application\Controllers\PersonController;

require_once __DIR__ . '/../../../../Infrastructure/Auth/middleware.php';

return function (Router $router, Container $container): void {
    $router->addRoute('GET', '/people-types', function () use ($container) {
        $controller = $container->make(PeopleTypeController::class);
        return $controller->list();
    });

    $router->addRoute('GET', '/people', function () use ($container) {
        $controller = $container->make(PersonController::class);
        return $controller->execute();
    });

    $router->addRoute('POST', '/people', middleware($container, function () use ($container) {
        $controller = $container->make(PersonController::class);
        return $controller->execute();
    }, ['ADM']));

    $router->addRoute('GET', '/people/{id:\d+}', function (string $id) use ($container) {
        $controller = $container->make(PersonController::class);
        return $controller->show((int) $id);
    });

    $router->addRoute('PUT', '/people/{id:\d+}', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(PersonController::class);
        return $controller->update((int) $id);
    }, ['ADM']));

    $router->addRoute('DELETE', '/people/{id:\d+}', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(PersonController::class);
        return $controller->delete((int) $id);
    }, ['ADM']));
};
