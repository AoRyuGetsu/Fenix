<?php

declare(strict_types=1);

namespace App\Core;

use FastRoute\RouteCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function FastRoute\simpleDispatcher;

class Router
{
    private array $routes = [];

    public function addRoute(string $method, string $path, callable|array $handler): void
    {
        $this->routes[] = [$method, $path, $handler];
    }

    public function registerFromFeatures(string $featuresDir, Container $container): void
    {
        foreach (glob(rtrim($featuresDir, '/') . '/*/Routes/routes.php') as $file) {
            $routes = require $file;
            $routes($this, $container);
        }
    }

    public function dispatch(Request $request): Response
    {
        $dispatcher = simpleDispatcher(function (RouteCollector $r) {
            foreach ($this->routes as [$method, $path, $handler]) {
                $r->addRoute($method, $path, $handler);
            }
        });

        $httpMethod = $request->getMethod();
        $uri = $request->getPathInfo();

        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

        return match ($routeInfo[0]) {
            \FastRoute\Dispatcher::FOUND => $this->resolveHandler($routeInfo[1], $routeInfo[2]),
            \FastRoute\Dispatcher::METHOD_NOT_ALLOWED => new Response(json_encode(['error' => 'Method not allowed']), 405, ['Content-Type' => 'application/json']),
            default => new Response(json_encode(['error' => 'Route not found']), 404, ['Content-Type' => 'application/json']),
        };
    }

    private function resolveHandler(callable|array $handler, array $params): Response
    {
        return call_user_func_array($handler, $params);
    }
}
