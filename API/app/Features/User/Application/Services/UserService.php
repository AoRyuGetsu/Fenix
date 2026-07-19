<?php

declare(strict_types=1);

namespace App\Features\User\Application\Services;

use App\Core\AppException;
use App\Core\Interfaces\PasswordHasherInterface;
use App\Features\RolesPermisos\Domain\Interfaces\RoleAccessControlServiceInterface;
use App\Features\People\Domain\Entities\PeopleType;
use App\Features\People\Domain\Entities\Person;
use App\Features\People\Domain\Entities\PersonUser;
use App\Features\User\Application\DTO\UserQuery;
use App\Features\User\Domain\Interfaces\UserServiceInterface;
use App\Features\User\Domain\Entities\User;
use App\Features\User\Domain\Interfaces\UserRepositoryInterface;
use Doctrine\ORM\EntityManager;

class UserService implements UserServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private PasswordHasherInterface $passwordHasher,
        private EntityManager $entityManager,
        private RoleAccessControlServiceInterface $roleAccessControl,
    ) {}

    public function create(string $email, string $password, ?array $personData = null): User
    {
        if ($personData === null) {
            $user = new User();
            $user->setEmail($email);
            $user->setPassword($this->passwordHasher->hash($password));
            $user->setActive(true);
            $user->setCreatedAt(new \DateTime());
            $user->setUpdatedAt(new \DateTime());

            $this->repository->save($user);

            return $user;
        }

        $existingUser = $this->repository->findByEmail($email);

        if ($existingUser !== null) {
            if ($existingUser->getDeletedAt() !== null) {
                throw new AppException('EMAIL_ALREADY_EXISTS');
            }

            if ($existingUser->getPerson() !== null) {
                throw new AppException('EMAIL_ALREADY_EXISTS');
            }

            $person = $this->createPerson($personData);
            $this->linkPersonToUser($person, $existingUser);

            return $existingUser;
        }

        $hashedPassword = $this->passwordHasher->hash($password);

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hashedPassword);
        $user->setActive(true);
        $user->setCreatedAt(new \DateTime());
        $user->setUpdatedAt(new \DateTime());

        $this->repository->save($user);

        $person = $this->createPerson($personData);
        $this->linkPersonToUser($person, $user);

        return $user;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->repository->findByEmail($email);
    }

    public function findById(int $id): ?User
    {
        return $this->repository->findById($id);
    }

    public function update(int $id, array $data): User
    {
        $user = $this->repository->findById($id);

        if ($user === null) {
            throw new AppException('USER_NOT_FOUND');
        }

        if ($user->getDeletedAt() !== null) {
            throw new AppException('USER_DEACTIVATED');
        }

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }

        if (isset($data['password'])) {
            $user->setPassword($this->passwordHasher->hash($data['password']));
        }

        if (isset($data['active'])) {
            $user->setActive((bool) $data['active']);
        }

        $previousType = null;
        if (isset($data['person']) && is_array($data['person'])) {
            $person = $user->getPerson();

            if ($person === null) {
                $person = $this->createPerson($data['person']);
                $this->linkPersonToUser($person, $user);
            } else {
                $previousType = $person->getPeopleType()->getCode();
                $this->updatePerson($person, $data['person']);
            }
        }

        $user->setUpdatedAt(new \DateTime());
        $this->repository->save($user);

        if (isset($data['person']['type'])) {
            $newType = $data['person']['type'];
            if ($previousType !== null && $previousType !== $newType) {
                $roles = $this->roleAccessControl->getUserRoles((int) $user->getId());
                foreach ($roles as $role) {
                    $this->roleAccessControl->removeRoleFromUser((int) $user->getId(), $role->getCode());
                }
                $this->roleAccessControl->assignRoleToUser((int) $user->getId(), $newType);
            } elseif ($previousType === null) {
                $this->roleAccessControl->assignRoleToUser((int) $user->getId(), $newType);
            }
        }

        return $user;
    }

    public function softDelete(int $id): void
    {
        $user = $this->repository->findById($id);

        if ($user === null) {
            throw new AppException('USER_NOT_FOUND');
        }

        $now = new \DateTime();

        $user->setDeletedAt($now);
        $user->setActive(false);

        $person = $user->getPerson();
        if ($person !== null) {
            $person->setDeletedAt($now);
        }

        $this->entityManager->flush();
    }

    public function restore(int $id): void
    {
        $user = $this->repository->findById($id);

        if ($user === null) {
            throw new AppException('USER_NOT_FOUND');
        }

        if ($user->getDeletedAt() === null) {
            return;
        }

        $user->setDeletedAt(null);
        $user->setDeletedBy(null);
        $user->setActive(true);

        $person = $user->getPerson();
        if ($person !== null) {
            $person->setDeletedAt(null);
        }

        $this->entityManager->flush();
    }

    public function list(?UserQuery $query = null): array
    {
        return $this->repository->findPaginated($query ?? new UserQuery());
    }

    private function createPerson(array $data): Person
    {
        $peopleType = $this->entityManager
            ->getRepository(PeopleType::class)
            ->findOneBy(['code' => $data['type']]);

        if ($peopleType === null) {
            throw new AppException('INVALID_TYPE');
        }

        $person = new Person();
        $person->setPeopleType($peopleType);
        $person->setFirstName($data['first_name']);
        $person->setMiddleName($data['middle_name'] ?? null);
        $person->setFirstSurname($data['first_surname']);
        $person->setSecondSurname($data['second_surname'] ?? null);
        $person->setDocumentNumber($data['document_number']);
        $person->setEmail($data['email']);
        $person->setPhone($data['phone'] ?? null);
        $person->setAddress($data['address'] ?? null);
        $person->setCreatedAt(new \DateTime());
        $person->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($person);
        $this->entityManager->flush();

        return $person;
    }

    private function updatePerson(Person $person, array $data): void
    {
        if (isset($data['first_name'])) {
            $person->setFirstName($data['first_name']);
        }
        if (array_key_exists('middle_name', $data)) {
            $person->setMiddleName($data['middle_name']);
        }
        if (isset($data['first_surname'])) {
            $person->setFirstSurname($data['first_surname']);
        }
        if (array_key_exists('second_surname', $data)) {
            $person->setSecondSurname($data['second_surname']);
        }
        if (isset($data['document_number'])) {
            $person->setDocumentNumber($data['document_number']);
        }
        if (array_key_exists('email', $data)) {
            $person->setEmail($data['email']);
        }
        if (array_key_exists('phone', $data)) {
            $person->setPhone($data['phone']);
        }
        if (array_key_exists('address', $data)) {
            $person->setAddress($data['address']);
        }
        if (isset($data['type'])) {
            $peopleType = $this->entityManager
                ->getRepository(PeopleType::class)
                ->findOneBy(['code' => $data['type']]);
            if ($peopleType === null) {
                throw new AppException('INVALID_TYPE');
            }
            $person->setPeopleType($peopleType);
        }

        $person->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();
    }

    private function linkPersonToUser(Person $person, User $user): void
    {
        $personUser = new PersonUser();
        $personUser->setPerson($person);
        $personUser->setUser($user);

        $this->entityManager->persist($personUser);
        $this->entityManager->flush();
    }
}
