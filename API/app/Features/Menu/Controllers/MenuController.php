<?php

declare(strict_types=1);

namespace App\Features\Menu\Controllers;

use App\Core\Contracts\ControllerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MenuController implements ControllerInterface
{
    public function execute(): Response
    {
        return new JsonResponse(['message' => 'Not implemented'], 501);
    }
}
