<?php

declare(strict_types=1);

namespace App\Features\Dashboard\Application\Controllers;

use App\Core\Interfaces\ControllerInterface;
use App\Features\Dashboard\Domain\Interfaces\DashboardServiceInterface;
use App\Infrastructure\Auth\AuthContext;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[OA\Tag(name: 'Dashboard', description: 'Dashboard y estadísticas')]
class DashboardController implements ControllerInterface
{
    public function __construct(
        private DashboardServiceInterface $dashboardService,
        private AuthContext $authContext,
    ) {}

    public function execute(): Response
    {
        return new JsonResponse(['error' => 'Method not allowed']);
    }

    #[OA\Get(
        path: '/dashboard',
        summary: 'Obtener estadísticas del dashboard',
        tags: ['Dashboard'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Estadísticas del dashboard',
                content: new OA\JsonContent(ref: '#/components/schemas/DashboardStats'),
            ),
        ],
    )]
    public function stats(): Response
    {
        $data = $this->dashboardService->getStats($this->authContext);
        return new JsonResponse($data);
    }
}
