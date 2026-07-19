<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Router;
use App\Features\Dashboard\Application\Controllers\DashboardController;

require_once __DIR__ . '/../../../../Infrastructure/Auth/middleware.php';

return function (Router $router, Container $container): void {
    $router->addRoute('GET', '/dashboard', middleware($container, function () use ($container) {
        $controller = $container->make(DashboardController::class);
        return $controller->stats();
    }, ['ADM', 'SUP', 'ENT', 'JUG', 'REP']));
};
