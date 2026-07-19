<?php

declare(strict_types=1);

use App\Core\Container;
use App\Infrastructure\Auth\AuthContext;
use App\Infrastructure\Auth\Domain\Interfaces\AuthServiceInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Crea un handler con middleware que:
 * 1. Valida JWT y crea AuthContext
 * 2. Verifica roles requeridos (opcional)
 * 3. Desactiva SoftDeleteFilter si es admin
 * 4. Inyecta AuthContext en el Container
 */
function middleware(Container $container, callable $handler, array $requiredRoles = []): callable
{
    return function () use ($container, $handler, $requiredRoles) {
        $request = $container->get(Request::class);
        $authHeader = $request->headers->get('Authorization');

        if ($authHeader === null || !str_starts_with($authHeader, 'Bearer ')) {
            return new JsonResponse(['error' => 'Token required'], 401);
        }

        $token = substr($authHeader, 7);

        try {
            $authService = $container->get(AuthServiceInterface::class);
            $claims = $authService->validateToken($token);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        $userId = (int) ($claims['sub'] ?? 0);
        $email = $claims['email'] ?? '';
        $roles = $claims['roles'] ?? [];
        $permissions = $claims['permissions'] ?? [];

        $authContext = new AuthContext($userId, $email, $roles, $permissions);
        $container->set(AuthContext::class, $authContext);

        if ($requiredRoles !== []) {
            $hasRole = false;
            foreach ($requiredRoles as $role) {
                if ($authContext->hasRole($role)) {
                    $hasRole = true;
                    break;
                }
            }
            if (!$hasRole) {
                return new JsonResponse(['error' => 'Forbidden'], 403);
            }
        }

        if ($authContext->isAdmin()) {
            try {
                $em = $container->get(EntityManager::class);
                $em->getFilters()->disable('soft-delete');
            } catch (\Throwable) {
                // EntityManager no disponible o filtro no registrado
            }
        }

        return $handler(...func_get_args());
    };
}
