<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Router;
use App\Features\RolesPermisos\Application\Controllers\PermissionController;
use App\Features\RolesPermisos\Application\Controllers\RoleController;
use App\Features\RolesPermisos\Application\Controllers\UserRoleController;

require_once __DIR__ . '/../../../../Infrastructure/Auth/middleware.php';

return function (Router $router, Container $container): void {
    $router->addRoute('POST', '/roles', middleware($container, function () use ($container) {
        $controller = $container->make(RoleController::class);
        return $controller->create();
    }, ['ADM']));

    $router->addRoute('GET', '/roles', middleware($container, function () use ($container) {
        $controller = $container->make(RoleController::class);
        return $controller->list();
    }));

    $router->addRoute('GET', '/roles/permissions', middleware($container, function () use ($container) {
        $controller = $container->make(RoleController::class);
        return $controller->listWithPermissions();
    }));

    $router->addRoute('DELETE', '/roles/{id:\d+}', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(RoleController::class);
        return $controller->delete((int) $id);
    }, ['ADM']));

    $router->addRoute('POST', '/roles/{id:\d+}/permissions', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(RoleController::class);
        return $controller->assignPermissions((int) $id);
    }, ['ADM']));

    $router->addRoute('POST', '/permissions', middleware($container, function () use ($container) {
        $controller = $container->make(PermissionController::class);
        return $controller->create();
    }, ['ADM']));

    $router->addRoute('GET', '/permissions', middleware($container, function () use ($container) {
        $controller = $container->make(PermissionController::class);
        return $controller->list();
    }));

    $router->addRoute('POST', '/users/{id:\d+}/roles', middleware($container, function (string $id) use ($container) {
        $controller = $container->make(UserRoleController::class);
        return $controller->assign((int) $id);
    }, ['ADM']));

    $router->addRoute('DELETE', '/users/{id:\d+}/roles/{roleId:\d+}', middleware($container, function (string $id, string $roleId) use ($container) {
        $controller = $container->make(UserRoleController::class);
        return $controller->remove((int) $id, (int) $roleId);
    }, ['ADM']));
};
