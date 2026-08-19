<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Router;
use App\Features\Representatives\Application\Controllers\RepresentativeController;

require_once __DIR__ . '/../../../../Infrastructure/Auth/middleware.php';

return function (Router $router, Container $container): void {
    $router->addRoute('GET', '/representatives/my/players', middleware($container, function () use ($container) {
        $controller = $container->make(RepresentativeController::class);
        return $controller->myPlayers();
    }, ['REP']));

    $router->addRoute('GET', '/representatives/{repUserId:\d+}/players', middleware($container, function (string $repUserId) use ($container) {
        $controller = $container->make(RepresentativeController::class);
        return $controller->listPlayers((int) $repUserId);
    }, ['ADM']));

    $router->addRoute('GET', '/players/{playerUserId:\d+}/representative', middleware($container, function (string $playerUserId) use ($container) {
        $controller = $container->make(RepresentativeController::class);
        return $controller->getRepresentative((int) $playerUserId);
    }, ['ADM']));

    $router->addRoute('POST', '/representatives/{repUserId:\d+}/players', middleware($container, function (string $repUserId) use ($container) {
        $controller = $container->make(RepresentativeController::class);
        return $controller->assignPlayers((int) $repUserId);
    }, ['ADM']));

    $router->addRoute('DELETE', '/representatives/{repUserId:\d+}/players/{playerUserId:\d+}', middleware($container, function (string $repUserId, string $playerUserId) use ($container) {
        $controller = $container->make(RepresentativeController::class);
        return $controller->removePlayer((int) $repUserId, (int) $playerUserId);
    }, ['ADM']));
};
