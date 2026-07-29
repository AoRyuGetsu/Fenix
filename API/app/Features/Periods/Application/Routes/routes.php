<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Router;
use App\Features\Periods\Application\Controllers\PeriodController;

require_once __DIR__ . '/../../../../Infrastructure/Auth/middleware.php';

return function (Router $router, Container $container): void {
    $router->addRoute('GET', '/periods', function () use ($container) {
        $controller = $container->make(PeriodController::class);
        return $controller->list();
    });

    $router->addRoute('GET', '/periods/{id:\d+}', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(PeriodController::class);
        return $controller->show((int) $id);
    }, []));

    $router->addRoute('POST', '/periods', middleware($container, function () use ($container) {
        $controller = $container->make(PeriodController::class);
        return $controller->create();
    }, ['ADM']));

    $router->addRoute('PUT', '/periods/{id:\d+}', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(PeriodController::class);
        return $controller->update((int) $id);
    }, ['ADM']));

    $router->addRoute('DELETE', '/periods/{id:\d+}', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(PeriodController::class);
        return $controller->delete((int) $id);
    }, ['ADM']));
};
