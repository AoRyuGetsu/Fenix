<?php

declare(strict_types=1);

namespace App\Features\People\Application\Services;

use App\Core\AppException;
use App\Features\RolesPermisos\Domain\Interfaces\RoleAccessControlServiceInterface;
use App\Features\People\Application\DTO\PersonQuery;
use App\Features\People\Domain\Interfaces\PersonServiceInterface;
use App\Features\People\Domain\Entities\Person;
use App\Features\People\Domain\Interfaces\PersonRepositoryInterface;
use App\Features\User\Domain\Interfaces\UserServiceInterface;
use App\Features\User\Domain\Entities\User;
use Doctrine\ORM\EntityManager;

class PersonService implements PersonServiceInterface
{
    public function __construct(
        private PersonRepositoryInterface $repository,
        private EntityManager $entityManager,
        private UserServiceInterface $userService,
        private RoleAccessControlServiceInterface $roleAccessControl,
    ) {}

    public function create(array $data): Person
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

        $this->repository->save($person);

        $createUser = (bool) ($data['create_user'] ?? true);

        if ($createUser) {
            $email = $data['email'] ?? null;
            if ($email === null) {
                throw new AppException('PASSWORD_REQUIRED');
            }

            $existingUser = $this->userService->findByEmail($email);

            if ($existingUser !== null) {
                if ($existingUser->getPerson() !== null) {
                    throw new AppException('USER_NOT_ASSOCIATED');
                }
                $user = $existingUser;
            } else {
                $password = $data['password'] ?? $data['document_number'];
                $user = $this->userService->create($email, $password);
                $this->roleAccessControl->assignRoleToUser((int) $user->getId(), $data['type']);
            }

            $personUser = new PersonUser();
            $personUser->setPerson($person);
            $personUser->setUser($user);

            $this->entityManager->persist($personUser);
            $this->entityManager->flush();
        }

        return $person;
    }

    public function findById(int $id): ?Person
    {
        return $this->repository->findById($id);
    }

    public function update(int $id, array $data): Person
    {
        $person = $this->repository->findById($id);

        if ($person === null) {
            throw new AppException('PERSON_NOT_FOUND');
        }

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
        if (isset($data['type'])) {
            $peopleType = $this->entityManager
                ->getRepository(PeopleType::class)
                ->findOneBy(['code' => $data['type']]);
            if ($peopleType === null) {
                throw new AppException('INVALID_TYPE');
            }
            $person->setPeopleType($peopleType);
        }
        if (array_key_exists('phone', $data)) {
            $person->setPhone($data['phone']);
        }
        if (array_key_exists('address', $data)) {
            $person->setAddress($data['address']);
        }
        $person->setUpdatedAt(new \DateTime());
        $this->repository->save($person);

        return $person;
    }

    public function softDelete(int $id): void
    {
        $person = $this->repository->findById($id);

        if ($person === null) {
            throw new AppException('PERSON_NOT_FOUND');
        }

        $this->repository->softDelete($person);
    }

    public function list(?PersonQuery $query = null): array
    {
        return $this->repository->findPaginated($query ?? new PersonQuery());
    }
}
