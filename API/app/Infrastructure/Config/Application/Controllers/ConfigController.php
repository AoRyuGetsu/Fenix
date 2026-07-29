<?php

declare(strict_types=1);

namespace App\Infrastructure\Config\Application\Controllers;

use App\Core\Interfaces\ControllerInterface;
use App\Core\Env;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Get(path: '/config', tags: ['Config'], summary: 'Obtiene configuración de la aplicación')]
#[OA\Response(response: 200, description: 'Configuración actual')]
class ConfigController implements ControllerInterface
{
    public function execute(): Response
    {
        return new Response(
            json_encode([
                'app_name'        => Env::get('APP_NAME', 'Analítica'),
                'api_url'         => Env::get('API_URL', ''),
                'jwt_access_ttl'  => Env::get('JWT_ACCESS_TTL', '3600'),
                'jwt_refresh_ttl' => Env::get('JWT_REFRESH_TTL', '604800'),
            ]),
            Response::HTTP_OK,
            ['Content-Type' => 'application/json']
        );
    }
}
