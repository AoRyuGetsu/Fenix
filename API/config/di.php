<?php

declare(strict_types=1);

use App\Core\Contracts\PasswordHasherInterface;
use App\Features\Auth\Contracts\AuthServiceInterface;
use App\Features\Auth\Contracts\RoleAccessControlServiceInterface;
use App\Features\Auth\Repositories\PermissionRepositoryInterface;
use App\Features\Auth\Repositories\RoleRepositoryInterface;
use App\Features\Auth\Repositories\PermissionRepository;
use App\Features\Auth\Repositories\RoleRepository;
use App\Features\Auth\Services\AuthService;
use App\Features\Auth\Services\RoleAccessControlService;
use App\Features\People\Contracts\PersonServiceInterface;
use App\Features\People\Repositories\PersonRepositoryInterface;
use App\Features\People\Repositories\PersonRepository;
use App\Features\People\Services\PersonService;
use App\Features\User\Contracts\UserServiceInterface;
use App\Features\User\Repositories\UserRepositoryInterface;
use App\Features\User\Repositories\UserRepository;
use App\Features\User\Services\UserService;
use App\Features\Menu\Contracts\MenuServiceInterface;
use App\Features\Menu\Repositories\MenuRepository;
use App\Features\Menu\Repositories\MenuRepositoryInterface;
use App\Features\Menu\Services\MenuService;
use App\Utils\Logger;
use App\Utils\Security\PasswordHasher;
use App\Utils\Validator;
use Doctrine\ORM\EntityManager;
use Monolog\Logger as MonologLogger;
use Symfony\Component\Validator\Validator\ValidatorInterface;

return [
    MonologLogger::class => DI\factory([Logger::class, 'getInstance']),
    ValidatorInterface::class => DI\factory([Validator::class, 'getInstance']),
    EntityManager::class => DI\factory(fn () => require __DIR__ . '/database.php'),
    PasswordHasherInterface::class => DI\autowire(PasswordHasher::class),
    UserServiceInterface::class => DI\autowire(UserService::class),
    UserRepositoryInterface::class => DI\autowire(UserRepository::class),
    AuthServiceInterface::class => DI\autowire(AuthService::class),
    RoleAccessControlServiceInterface::class => DI\autowire(RoleAccessControlService::class),
    RoleRepositoryInterface::class => DI\autowire(RoleRepository::class),
    PermissionRepositoryInterface::class => DI\autowire(PermissionRepository::class),
    MenuServiceInterface::class => DI\autowire(MenuService::class),
    MenuRepositoryInterface::class => DI\autowire(MenuRepository::class),
    PersonServiceInterface::class => DI\autowire(PersonService::class),
    PersonRepositoryInterface::class => DI\autowire(PersonRepository::class),
];
