<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Router;
use App\Features\Config\Controllers\ConfigController;

return function (Router $router, Container $container): void {
    $router->addRoute('GET', '/config', function () use ($container) {
        $controller = $container->make(ConfigController::class);
        return $controller->execute();
    });
};
