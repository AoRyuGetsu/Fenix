<?php

declare(strict_types=1);

use App\Core\Interfaces\PasswordHasherInterface;
use App\Features\RolesPermisos\Domain\Interfaces\RoleAccessControlServiceInterface;
use App\Infrastructure\Auth\AuthContext;
use App\Features\RolesPermisos\Domain\Interfaces\PermissionRepositoryInterface;
use App\Features\RolesPermisos\Domain\Interfaces\RoleRepositoryInterface;
use App\Features\RolesPermisos\Domain\Repositories\PermissionRepository;
use App\Features\RolesPermisos\Domain\Repositories\RoleRepository;
use App\Features\RolesPermisos\Application\Services\RoleAccessControlService;
use App\Infrastructure\Auth\Domain\Interfaces\AuthServiceInterface;
use App\Infrastructure\Auth\Application\Services\AuthService;
use App\Features\People\Domain\Interfaces\PeopleTypeServiceInterface;
use App\Features\People\Domain\Interfaces\PersonServiceInterface;
use App\Features\People\Domain\Interfaces\PeopleTypeRepositoryInterface;
use App\Features\People\Domain\Repositories\PeopleTypeRepository;
use App\Features\People\Domain\Interfaces\PersonRepositoryInterface;
use App\Features\People\Domain\Repositories\PersonRepository;
use App\Features\People\Application\Services\PeopleTypeService;
use App\Features\People\Application\Services\PersonService;
use App\Features\User\Domain\Interfaces\UserServiceInterface;
use App\Features\User\Domain\Interfaces\UserRepositoryInterface;
use App\Features\User\Domain\Repositories\UserRepository;
use App\Features\User\Application\Services\UserService;
use App\Infrastructure\Menu\Domain\Interfaces\MenuServiceInterface;
use App\Infrastructure\Menu\Domain\Repositories\MenuRepository;
use App\Infrastructure\Menu\Domain\Interfaces\MenuRepositoryInterface;
use App\Infrastructure\Menu\Application\Services\MenuService;
use App\Features\Questionnaire\Domain\Interfaces\EvaluationRepositoryInterface;
use App\Features\Questionnaire\Domain\Interfaces\EvaluationServiceInterface;
use App\Features\Questionnaire\Domain\Interfaces\QuestionnaireRepositoryInterface;
use App\Features\Questionnaire\Domain\Interfaces\QuestionnaireServiceInterface;
use App\Features\Questionnaire\Domain\Interfaces\SectionServiceInterface;
use App\Features\Questionnaire\Domain\Interfaces\SectionRepositoryInterface;
use App\Features\Dashboard\Domain\Interfaces\DashboardServiceInterface;
use App\Features\Dashboard\Application\Services\DashboardService;
use App\Features\Periods\Domain\Interfaces\PeriodRepositoryInterface;
use App\Features\Periods\Domain\Interfaces\PeriodServiceInterface;
use App\Features\Periods\Domain\Repositories\PeriodRepository;
use App\Features\Periods\Application\Services\PeriodService;
use App\Features\Questionnaire\Domain\Interfaces\QuestionServiceInterface;
use App\Features\Questionnaire\Domain\Interfaces\QuestionRepositoryInterface;
use App\Features\Questionnaire\Domain\Repositories\EvaluationRepository;
use App\Features\Questionnaire\Domain\Repositories\QuestionnaireRepository;
use App\Features\Questionnaire\Domain\Repositories\QuestionRepository;
use App\Features\Questionnaire\Application\Services\EvaluationService;
use App\Features\Questionnaire\Application\Services\QuestionnaireService;
use App\Features\Questionnaire\Application\Services\QuestionService;
use App\Features\Questionnaire\Application\Services\SectionService;
use App\Features\Questionnaire\Domain\Repositories\SectionRepository;
use App\Utils\Logger;
use App\Utils\Security\PasswordHasher;
use App\Utils\Validator;
use Doctrine\ORM\EntityManager;
use Monolog\Logger as MonologLogger;
use Symfony\Component\Validator\Validator\ValidatorInterface;

return [
    AuthContext::class => null,

    MonologLogger::class => DI\factory(fn () => Logger::getInstance()),
    ValidatorInterface::class => DI\factory(fn () => Validator::getInstance()),
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
    PeopleTypeServiceInterface::class => DI\autowire(PeopleTypeService::class),
    PeopleTypeRepositoryInterface::class => DI\autowire(PeopleTypeRepository::class),
    QuestionnaireServiceInterface::class => DI\autowire(QuestionnaireService::class),
    QuestionnaireRepositoryInterface::class => DI\autowire(QuestionnaireRepository::class),
    QuestionServiceInterface::class => DI\autowire(QuestionService::class),
    QuestionRepositoryInterface::class => DI\autowire(QuestionRepository::class),
    EvaluationServiceInterface::class => DI\autowire(EvaluationService::class),
    EvaluationRepositoryInterface::class => DI\autowire(EvaluationRepository::class),
    PeriodRepositoryInterface::class => DI\autowire(PeriodRepository::class),
    PeriodServiceInterface::class => DI\autowire(PeriodService::class),
    SectionServiceInterface::class => DI\autowire(SectionService::class),
    SectionRepositoryInterface::class => DI\autowire(SectionRepository::class),
    DashboardServiceInterface::class => DI\autowire(DashboardService::class),
];
