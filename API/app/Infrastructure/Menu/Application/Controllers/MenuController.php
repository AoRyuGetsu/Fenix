<?php

declare(strict_types=1);

namespace App\Infrastructure\Menu\Application\Controllers;

use App\Core\Interfaces\ControllerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MenuController implements ControllerInterface
{
    public function execute(): Response
    {
        return new JsonResponse(['message' => 'Not implemented'], 501);
    }
}
