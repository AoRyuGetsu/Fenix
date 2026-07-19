<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Router;
use App\Infrastructure\Health\Application\Controllers\HealthController;

return function (Router $router, Container $container): void {
    $router->addRoute('GET', '/health', function () use ($container) {
        $controller = $container->make(HealthController::class);
        return $controller->execute();
    });
};
