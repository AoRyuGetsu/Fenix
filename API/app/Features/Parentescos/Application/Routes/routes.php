<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Router;
use App\Features\Parentescos\Application\Controllers\ParentescoController;

return function (Router $router, Container $container): void {
    $router->addRoute('GET', '/parentescos', function () use ($container) {
        $controller = $container->make(ParentescoController::class);
        return $controller->list();
    });
};
