<?php

declare(strict_types=1);

namespace App\Core\Interfaces;

use Symfony\Component\HttpFoundation\Response;

interface ControllerInterface
{
    public function execute(): Response;
}
