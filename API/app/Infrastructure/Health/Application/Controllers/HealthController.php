<?php

declare(strict_types=1);

namespace App\Infrastructure\Health\Application\Controllers;

use App\Core\Interfaces\ControllerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Get(path: '/health', tags: ['Health'], summary: 'Health check')]
#[OA\Response(response: 200, description: 'API funcionando')]
class HealthController implements ControllerInterface
{
    public function execute(): Response
    {
        return new Response(
            json_encode([
                'status' => 'ok',
                'message' => 'API is running',
            ]),
            Response::HTTP_OK,
            ['Content-Type' => 'application/json']
        );
    }
}
