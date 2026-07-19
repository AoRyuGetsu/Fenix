<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth\Domain\Interfaces;

interface AuthServiceInterface
{
    public function login(string $email, string $password): array;

    public function refreshToken(string $refreshToken): array;

    public function validateToken(string $token): array;
}
