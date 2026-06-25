<?php

declare(strict_types=1);

namespace App\Core\OpenApi;

use OpenApi\Generator;
use Symfony\Component\HttpFoundation\Response;

class OpenApiController
{
    public function spec(): Response
    {
        $generator = new Generator();
        $openapi = $generator->generate([__DIR__ . '/../../']);

        return new Response(
            $openapi->toJson(),
            Response::HTTP_OK,
            ['Content-Type' => 'application/json'],
        );
    }

    public function ui(): Response
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Analítica API - Documentación</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        SwaggerUIBundle({
            url: '/api-docs',
            dom_id: '#swagger-ui',
        });
    </script>
</body>
</html>
HTML;

        return new Response($html, Response::HTTP_OK, ['Content-Type' => 'text/html']);
    }
}
