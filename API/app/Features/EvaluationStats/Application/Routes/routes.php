<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Router;
use App\Features\EvaluationStats\Application\Controllers\EvaluationStatsController;

require_once __DIR__ . '/../../../../Infrastructure/Auth/middleware.php';

return function (Router $router, Container $container): void {
    $router->addRoute('GET', '/evaluation-stats', middleware($container, function () use ($container) {
        $controller = $container->make(EvaluationStatsController::class);
        return $controller->stats();
    }));

    $router->addRoute('GET', '/evaluation-stats/team/{id}', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(EvaluationStatsController::class);
        return $controller->teamStats((int) $id);
    }, ['ADM', 'SUP', 'ENT']));
};
