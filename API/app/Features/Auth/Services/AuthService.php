<?php

declare(strict_types=1);

namespace App\Features\Auth\Services;

use App\Core\Contracts\PasswordHasherInterface;
use App\Core\Env;
use App\Features\Auth\Contracts\AuthServiceInterface;
use App\Features\Auth\Contracts\RoleAccessControlServiceInterface;
use App\Features\User\Contracts\UserServiceInterface;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;

class AuthService implements AuthServiceInterface
{
    private Configuration $config;

    public function __construct(
        private UserServiceInterface $userService,
        private PasswordHasherInterface $passwordHasher,
        private RoleAccessControlServiceInterface $roleAccessControl,
    ) {
        $secret = Env::get('JWT_SECRET', '');
        $this->config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::base64Encoded($secret),
        );

        $this->config->setValidationConstraints(
            new SignedWith($this->config->signer(), $this->config->signingKey()),
            new StrictValidAt(new SystemClock(new \DateTimeZone('UTC'))),
        );
    }

    public function login(string $email, string $password): array
    {
        $user = $this->userService->findByEmail($email);

        if ($user === null || !$this->passwordHasher->verify($password, $user->getPassword())) {
            throw new \RuntimeException('Invalid credentials');
        }

        $roles = $this->roleAccessControl->getUserRoles($user->getId());
        $permissions = $this->roleAccessControl->getUserPermissions($user->getId());

        $roleNames = array_map(fn ($r) => $r->getName(), $roles);
        $permissionNames = array_map(fn ($p) => $p->getName(), $permissions);

        $accessToken = $this->createToken(
            (string) $user->getId(),
            $user->getEmail(),
            $roleNames,
            $permissionNames,
            (int) Env::get('JWT_ACCESS_TTL', '3600'),
        );

        $refreshToken = $this->createToken(
            (string) $user->getId(),
            $user->getEmail(),
            $roleNames,
            $permissionNames,
            (int) Env::get('JWT_REFRESH_TTL', '604800'),
            'refresh',
        );

        return [
            'access_token'  => $accessToken->toString(),
            'refresh_token' => $refreshToken->toString(),
            'expires_in'    => (int) Env::get('JWT_ACCESS_TTL', '3600'),
            'user'          => [
                'id'    => $user->getId(),
                'email' => $user->getEmail(),
            ],
            'menus' => array_map(fn ($m) => [
                'id'         => $m->getId(),
                'label'      => $m->getLabel(),
                'icon'       => $m->getIcon(),
                'route'      => $m->getRoute(),
                'parent_id'  => $m->getParent()?->getId(),
                'menu_order' => $m->getMenuOrder(),
            ], $this->roleAccessControl->getUserMenus($user->getId())),
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        $claims = $this->validateToken($refreshToken);

        if (($claims['purpose'] ?? null) !== 'refresh') {
            throw new \RuntimeException('Invalid token purpose');
        }

        $userId = (int) $claims['sub'];
        $user = $this->userService->findById($userId);

        if ($user === null) {
            throw new \RuntimeException('User not found');
        }

        $roles = $this->roleAccessControl->getUserRoles($userId);
        $permissions = $this->roleAccessControl->getUserPermissions($userId);

        $roleNames = array_map(fn ($r) => $r->getName(), $roles);
        $permissionNames = array_map(fn ($p) => $p->getName(), $permissions);

        $accessToken = $this->createToken(
            (string) $userId,
            $user->getEmail(),
            $roleNames,
            $permissionNames,
            (int) Env::get('JWT_ACCESS_TTL', '3600'),
        );

        $newRefreshToken = $this->createToken(
            (string) $userId,
            $user->getEmail(),
            $roleNames,
            $permissionNames,
            (int) Env::get('JWT_REFRESH_TTL', '604800'),
            'refresh',
        );

        return [
            'access_token'  => $accessToken->toString(),
            'refresh_token' => $newRefreshToken->toString(),
            'expires_in'    => (int) Env::get('JWT_ACCESS_TTL', '3600'),
            'menus' => array_map(fn ($m) => [
                'id'         => $m->getId(),
                'label'      => $m->getLabel(),
                'icon'       => $m->getIcon(),
                'route'      => $m->getRoute(),
                'parent_id'  => $m->getParent()?->getId(),
                'menu_order' => $m->getMenuOrder(),
            ], $this->roleAccessControl->getUserMenus($userId)),
        ];
    }

    public function validateToken(string $token): array
    {
        $parsed = $this->config->parser()->parse($token);

        if (!$parsed instanceof Plain) {
            throw new \RuntimeException('Invalid token');
        }

        if (!$this->config->validator()->validate($parsed, ...$this->config->validationConstraints())) {
            throw new \RuntimeException('Token validation failed');
        }

        $claims = $parsed->claims();
        $data = [];

        foreach ($claims->all() as $name => $value) {
            $data[$name] = $value;
        }

        return $data;
    }

    private function createToken(
        string $userId,
        string $email,
        array $roles,
        array $permissions,
        int $ttl,
        ?string $purpose = null,
    ): Plain {
        $now = new \DateTimeImmutable();
        $builder = $this->config->builder()
            ->issuedBy(Env::get('JWT_ISSUER', 'analitica-api'))
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify("+{$ttl}seconds"))
            ->relatedTo($userId)
            ->withClaim('email', $email)
            ->withClaim('roles', $roles)
            ->withClaim('permissions', $permissions);

        if ($purpose !== null) {
            $builder = $builder->withClaim('purpose', $purpose);
        }

        return $builder->getToken($this->config->signer(), $this->config->signingKey());
    }
}
