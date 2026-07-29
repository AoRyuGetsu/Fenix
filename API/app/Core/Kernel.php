<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\OpenApi\OpenApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Kernel
{
    private Container $container;
    private Router $router;

    public function __construct()
    {
        $this->container = new Container();
        $this->router = new Router();

        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        $this->router->registerFromFeatures(
            __DIR__ . '/../Features',
            $this->container,
        );

        $this->router->registerFromFeatures(
            __DIR__ . '/../Infrastructure',
            $this->container,
        );

        $this->router->addRoute('GET', '/api-docs', function () {
            $controller = new OpenApiController();
            return $controller->spec();
        });

        $this->router->addRoute('GET', '/docs', function () {
            $controller = new OpenApiController();
            return $controller->ui();
        });
    }

    public function handle(Request $request): Response
    {
        if ($request->getMethod() === 'OPTIONS') {
            $response = new Response('', 204);
        } else {
            $this->container->set(Request::class, $request);
            try {
                $response = $this->router->dispatch($request);
            } catch (\Throwable $e) {
                $logger = $this->container->get(\Monolog\Logger::class);
                $logger->error('Unhandled exception in request', [
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                    'trace'   => $e->getTraceAsString(),
                    'url'     => $request->getMethod() . ' ' . $request->getRequestUri(),
                ]);
                $response = new JsonResponse(['error' => getError('INTERNAL_ERROR'), 'code' => 'INTERNAL_ERROR'], 500);
            }
        }

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');

        return $response;
    }
}
