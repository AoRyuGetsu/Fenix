<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Router;
use App\Features\Questionnaire\Application\Controllers\EvaluationController;
use App\Features\Questionnaire\Application\Controllers\QuestionController;
use App\Features\Questionnaire\Application\Controllers\QuestionnaireController;
use App\Features\Questionnaire\Application\Controllers\SectionController;

require_once __DIR__ . '/../../../../Infrastructure/Auth/middleware.php';

return function (Router $router, Container $container): void {
    $router->addRoute('GET', '/questionnaires', middleware($container, function () use ($container) {
        $controller = $container->make(QuestionnaireController::class);
        return $controller->list();
    }));

    $router->addRoute('POST', '/questionnaires', middleware($container, function () use ($container) {
        $controller = $container->make(QuestionnaireController::class);
        return $controller->create();
    }, ['ADM']));

    $router->addRoute('GET', '/questionnaires/{id:\d+}', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(QuestionnaireController::class);
        return $controller->show((int) $id);
    }));

    $router->addRoute('PUT', '/questionnaires/{id:\d+}', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(QuestionnaireController::class);
        return $controller->update((int) $id);
    }, ['ADM']));

    $router->addRoute('DELETE', '/questionnaires/{id:\d+}', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(QuestionnaireController::class);
        return $controller->delete((int) $id);
    }, ['ADM']));

    // Sections
    $router->addRoute('GET', '/questionnaires/{id:\d+}/sections', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(SectionController::class);
        return $controller->list((int) $id);
    }));

    $router->addRoute('POST', '/questionnaires/{id:\d+}/sections', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(SectionController::class);
        return $controller->create((int) $id);
    }, ['ADM']));

    $router->addRoute('PUT', '/sections/{id:\d+}', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(SectionController::class);
        return $controller->update((int) $id);
    }, ['ADM']));

    $router->addRoute('DELETE', '/sections/{id:\d+}', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(SectionController::class);
        return $controller->delete((int) $id);
    }, ['ADM']));

    // Questions (ahora por sección)
    $router->addRoute('GET', '/sections/{id:\d+}/questions', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(QuestionController::class);
        return $controller->list((int) $id);
    }));

    $router->addRoute('POST', '/sections/{id:\d+}/questions', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(QuestionController::class);
        return $controller->create((int) $id);
    }, ['ADM']));

    $router->addRoute('PUT', '/questions/{id:\d+}', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(QuestionController::class);
        return $controller->update((int) $id);
    }, ['ADM']));

    $router->addRoute('DELETE', '/questions/{id:\d+}', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(QuestionController::class);
        return $controller->delete((int) $id);
    }, ['ADM']));

    // Evaluations — static routes FIRST (FastRoute exige estáticas antes de variables)
    $router->addRoute('GET', '/evaluations/my-questionnaires', middleware($container, function () use ($container) {
        $controller = $container->make(EvaluationController::class);
        return $controller->myQuestionnaires();
    }));

    $router->addRoute('GET', '/evaluations/period-targets', middleware($container, function () use ($container) {
        $controller = $container->make(EvaluationController::class);
        return $controller->periodTargets();
    }));

    $router->addRoute('POST', '/evaluations', middleware($container, function () use ($container) {
        $controller = $container->make(EvaluationController::class);
        return $controller->create();
    }));

    $router->addRoute('GET', '/evaluations', middleware($container, function () use ($container) {
        $controller = $container->make(EvaluationController::class);
        return $controller->list();
    }));

    // Evaluations — variable routes AFTER todas las estáticas
    $router->addRoute('GET', '/evaluations/{id:\d+}', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(EvaluationController::class);
        return $controller->show((int) $id);
    }));

    $router->addRoute('PUT', '/evaluations/{id:\d+}', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(EvaluationController::class);
        return $controller->update((int) $id);
    }));

    $router->addRoute('DELETE', '/evaluations/{id:\d+}', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(EvaluationController::class);
        return $controller->delete((int) $id);
    }, ['ADM']));
};
