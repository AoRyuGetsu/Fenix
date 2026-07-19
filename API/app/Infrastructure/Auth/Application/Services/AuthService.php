<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth\Application\Services;

use App\Core\AppException;
use App\Core\Interfaces\PasswordHasherInterface;
use App\Core\Env;
use App\Features\RolesPermisos\Domain\Interfaces\RoleAccessControlServiceInterface;
use App\Features\User\Domain\Entities\User;
use App\Features\User\Domain\Interfaces\UserServiceInterface;
use App\Infrastructure\Auth\Domain\Interfaces\AuthServiceInterface;
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
            throw new AppException('INVALID_CREDENTIALS');
        }

        return $this->buildAuthResponse($user, includeUser: true);
    }

    public function refreshToken(string $refreshToken): array
    {
        $claims = $this->validateToken($refreshToken);

        if (($claims['purpose'] ?? null) !== 'refresh') {
            throw new AppException('INVALID_TOKEN_PURPOSE');
        }

        $userId = (int) $claims['sub'];
        $user = $this->userService->findById($userId);

        if ($user === null) {
            throw new AppException('USER_NOT_FOUND');
        }

        return $this->buildAuthResponse($user, includeUser: false);
    }

    public function validateToken(string $token): array
    {
        $parsed = $this->config->parser()->parse($token);

        if (!$parsed instanceof Plain) {
            throw new AppException('TOKEN_INVALID');
        }

        if (!$this->config->validator()->validate($parsed, ...$this->config->validationConstraints())) {
            throw new AppException('TOKEN_EXPIRED');
        }

        $claims = $parsed->claims();
        $data = [];

        foreach ($claims->all() as $name => $value) {
            $data[$name] = $value;
        }

        return $data;
    }

    private function buildAuthResponse(User $user, bool $includeUser): array
    {
        $roles = $this->roleAccessControl->getUserRoles($user->getId());
        $permissions = $this->roleAccessControl->getUserPermissions($user->getId());

        $roleCodes = array_map(fn ($r) => $r->getCode(), $roles);
        $permissionNames = array_map(fn ($p) => $p->getName(), $permissions);

        $accessToken = $this->createToken(
            (string) $user->getId(),
            $user->getEmail(),
            $roleCodes,
            $permissionNames,
            (int) Env::get('JWT_ACCESS_TTL', '3600'),
        );

        $refreshToken = $this->createToken(
            (string) $user->getId(),
            $user->getEmail(),
            $roleCodes,
            $permissionNames,
            (int) Env::get('JWT_REFRESH_TTL', '604800'),
            'refresh',
        );

        $allRoles = $this->roleAccessControl->listRoles();

        $response = [
            'access_token'  => $accessToken->toString(),
            'refresh_token' => $refreshToken->toString(),
            'expires_in'    => (int) Env::get('JWT_ACCESS_TTL', '3600'),
            'roles'         => $roleCodes,
            'menus' => array_map(fn ($m) => $m->toArray(), $this->roleAccessControl->getUserMenus($user->getId())),
            'catalogs' => [
                'roles' => array_map(fn ($r) => [
                    'id'   => $r->getId(),
                    'code' => $r->getCode(),
                    'name' => $r->getName(),
                ], $allRoles),
            ],
        ];

        if ($includeUser) {
            $person = $user->getPerson();
            $personData = null;
            if ($person !== null) {
                $fullName = trim(implode(' ', array_filter([
                    $person->getFirstName(),
                    $person->getMiddleName(),
                    $person->getFirstSurname(),
                    $person->getSecondSurname(),
                ])));
                $personData = ['full_name' => $fullName];
            }

            $response['user'] = [
                'id'     => $user->getId(),
                'email'  => $user->getEmail(),
                'person' => $personData,
            ];
        }

        return $response;
    }

    private function createToken(
        string $userId,
        string $email,
        array $roles,
        array $permissions,
        int $ttl,
        ?string $purpose = null,
    ): Plain {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
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
