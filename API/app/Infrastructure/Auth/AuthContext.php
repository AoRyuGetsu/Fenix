<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

class AuthContext
{
    public function __construct(
        private int $userId,
        private string $email,
        private array $roles,
        private array $permissions,
    ) {}

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function isAdmin(): bool
    {
        return in_array('ADM', $this->roles, true);
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }
}
