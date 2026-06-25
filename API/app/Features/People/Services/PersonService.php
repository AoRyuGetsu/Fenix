<?php

declare(strict_types=1);

namespace App\Features\People\Services;

use App\Features\Auth\Contracts\RoleAccessControlServiceInterface;
use App\Features\People\Contracts\PersonQuery;
use App\Features\People\Contracts\PersonServiceInterface;
use App\Features\People\Models\Person;
use App\Features\People\Models\PersonUser;
use App\Features\People\Repositories\PersonRepositoryInterface;
use App\Features\User\Contracts\UserServiceInterface;
use App\Features\User\Models\User;
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
        $person = new Person();
        $person->setFirstName($data['first_name']);
        $person->setMiddleName($data['middle_name'] ?? null);
        $person->setFirstSurname($data['first_surname']);
        $person->setSecondSurname($data['second_surname'] ?? null);
        $person->setIdNumber($data['id_number']);
        $person->setEmail($data['email'] ?? null);
        $person->setPhone($data['phone'] ?? null);
        $person->setAddress($data['address'] ?? null);
        $person->setType($data['type']);
        $person->setCreatedAt(new \DateTime());
        $person->setUpdatedAt(new \DateTime());

        $this->repository->save($person);

        $createUser = (bool) ($data['create_user'] ?? true);

        if ($createUser) {
            $email = $data['email'] ?? null;
            if ($email === null) {
                throw new \RuntimeException('email is required to create user');
            }

            $existingUser = $this->userService->findByEmail($email);

            if ($existingUser !== null) {
                if ($existingUser->getPerson() !== null) {
                    throw new \RuntimeException('The user already has an associated person');
                }
                $user = $existingUser;
            } else {
                $user = $this->userService->create($email, $data['id_number']);
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

    public function findByUserId(int $userId): ?Person
    {
        $personUser = $this->entityManager
            ->getRepository(PersonUser::class)
            ->findOneBy(['user' => $userId]);

        return $personUser?->getPerson();
    }

    public function findByIdNumber(string $idNumber): ?Person
    {
        return $this->repository->findByIdNumber($idNumber);
    }

    public function update(int $id, array $data): Person
    {
        $person = $this->repository->findById($id);

        if ($person === null) {
            throw new \RuntimeException('Person not found');
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
        if (isset($data['id_number'])) {
            $person->setIdNumber($data['id_number']);
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
            $person->setType($data['type']);
        }

        $person->setUpdatedAt(new \DateTime());
        $this->repository->save($person);

        return $person;
    }

    public function softDelete(int $id): void
    {
        $person = $this->repository->findById($id);

        if ($person === null) {
            throw new \RuntimeException('Person not found');
        }

        $this->repository->softDelete($person);
    }

    public function list(?PersonQuery $query = null): array
    {
        $criteria = [];

        if ($query !== null) {
            if ($query->type !== null) {
                $criteria['type'] = $query->type;
            }
            if ($query->idNumber !== null) {
                $criteria['idNumber'] = $query->idNumber;
            }
        }

        return $this->repository->findAll($criteria);
    }
}
