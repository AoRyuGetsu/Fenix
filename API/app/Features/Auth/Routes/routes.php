<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Router;
use App\Features\Auth\Controllers\AuthController;
use App\Features\Auth\Controllers\PermissionController;
use App\Features\Auth\Controllers\RoleController;
use App\Features\Auth\Controllers\UserRoleController;

return function (Router $router, Container $container): void {
    $router->addRoute('POST', '/auth/login', function () use ($container) {
        $controller = $container->make(AuthController::class);
        return $controller->login();
    });

    $router->addRoute('POST', '/auth/refresh', function () use ($container) {
        $controller = $container->make(AuthController::class);
        return $controller->refresh();
    });

    $router->addRoute('GET', '/auth/me', function () use ($container) {
        $controller = $container->make(AuthController::class);
        return $controller->me();
    });

    $router->addRoute('POST', '/roles', function () use ($container) {
        $controller = $container->make(RoleController::class);
        return $controller->create();
    });

    $router->addRoute('GET', '/roles', function () use ($container) {
        $controller = $container->make(RoleController::class);
        return $controller->list();
    });

    $router->addRoute('DELETE', '/roles/{id}', function (string $id) use ($container) {
        $controller = $container->make(RoleController::class);
        return $controller->delete((int) $id);
    });

    $router->addRoute('POST', '/roles/{id}/permissions', function (string $id) use ($container) {
        $controller = $container->make(RoleController::class);
        return $controller->assignPermissions((int) $id);
    });

    $router->addRoute('POST', '/permissions', function () use ($container) {
        $controller = $container->make(PermissionController::class);
        return $controller->create();
    });

    $router->addRoute('GET', '/permissions', function () use ($container) {
        $controller = $container->make(PermissionController::class);
        return $controller->list();
    });

    $router->addRoute('POST', '/users/{id}/roles', function (string $id) use ($container) {
        $controller = $container->make(UserRoleController::class);
        return $controller->assign((int) $id);
    });

    $router->addRoute('DELETE', '/users/{id}/roles/{roleId}', function (string $id, string $roleId) use ($container) {
        $controller = $container->make(UserRoleController::class);
        return $controller->remove((int) $id, (int) $roleId);
    });
};
