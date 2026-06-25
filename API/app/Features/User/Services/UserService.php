<?php

declare(strict_types=1);

namespace App\Features\User\Services;

use App\Core\Contracts\PasswordHasherInterface;
use App\Features\User\Contracts\UserQuery;
use App\Features\User\Contracts\UserServiceInterface;
use App\Features\User\Models\User;
use App\Features\User\Repositories\UserRepositoryInterface;

class UserService implements UserServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private PasswordHasherInterface $passwordHasher,
    ) {}

    public function create(string $email, string $password): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hash($password));
        $user->setCreatedAt(new \DateTime());
        $user->setUpdatedAt(new \DateTime());

        $this->repository->save($user);

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
            throw new \RuntimeException('User not found');
        }

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }

        if (isset($data['password'])) {
            $user->setPassword($this->passwordHasher->hash($data['password']));
        }

        $user->setUpdatedAt(new \DateTime());

        $this->repository->save($user);

        return $user;
    }

    public function softDelete(int $id): void
    {
        $user = $this->repository->findById($id);

        if ($user === null) {
            throw new \RuntimeException('User not found');
        }

        $this->repository->softDelete($user);
    }

    public function list(?UserQuery $query = null): array
    {
        $criteria = [];

        if ($query !== null && $query->email !== null) {
            $criteria['email'] = $query->email;
        }

        return $this->repository->findAll($criteria);
    }
}
