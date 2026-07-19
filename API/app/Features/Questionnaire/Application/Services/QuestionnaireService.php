<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Application\Services;

use App\Core\AppException;
use App\Features\RolesPermisos\Domain\Interfaces\RoleRepositoryInterface;
use App\Features\Questionnaire\Application\DTO\QuestionnaireQuery;
use App\Features\Questionnaire\Domain\Interfaces\QuestionnaireRepositoryInterface;
use App\Features\Questionnaire\Domain\Interfaces\QuestionnaireServiceInterface;
use App\Features\Questionnaire\Domain\Entities\Questionnaire;

class QuestionnaireService implements QuestionnaireServiceInterface
{
    public function __construct(
        private QuestionnaireRepositoryInterface $repository,
        private RoleRepositoryInterface $roleRepository,
    ) {}

    public function create(array $data): Questionnaire
    {
        $responderRole = $this->roleRepository->findById((int) ($data['responder_role_id'] ?? 0));
        if ($responderRole === null) {
            throw new AppException('ROLE_NOT_FOUND');
        }

        $targetRole = $this->roleRepository->findById((int) ($data['target_role_id'] ?? 0));
        if ($targetRole === null) {
            throw new AppException('ROLE_NOT_FOUND');
        }

        $questionnaire = new Questionnaire();
        $questionnaire->setTitle($data['title']);
        $questionnaire->setDescription($data['description'] ?? null);
        $questionnaire->setActive(true);
        $questionnaire->setResponderRole($responderRole);
        $questionnaire->setTargetRole($targetRole);
        $questionnaire->setCreatedAt(new \DateTime());
        $questionnaire->setUpdatedAt(new \DateTime());

        $this->repository->save($questionnaire);

        return $questionnaire;
    }

    public function update(int $id, array $data): Questionnaire
    {
        $questionnaire = $this->repository->findById($id);
        if ($questionnaire === null) {
            throw new AppException('QUESTIONNAIRE_NOT_FOUND');
        }

        if (isset($data['title'])) {
            $questionnaire->setTitle($data['title']);
        }
        if (array_key_exists('description', $data)) {
            $questionnaire->setDescription($data['description']);
        }
        if (isset($data['active'])) {
            $questionnaire->setActive((bool) $data['active']);
        }
        if (isset($data['responder_role_id'])) {
            $role = $this->roleRepository->findById((int) $data['responder_role_id']);
            if ($role === null) {
                throw new AppException('ROLE_NOT_FOUND');
            }
            $questionnaire->setResponderRole($role);
        }
        if (isset($data['target_role_id'])) {
            $role = $this->roleRepository->findById((int) $data['target_role_id']);
            if ($role === null) {
                throw new AppException('ROLE_NOT_FOUND');
            }
            $questionnaire->setTargetRole($role);
        }

        $questionnaire->setUpdatedAt(new \DateTime());
        $this->repository->save($questionnaire);

        return $questionnaire;
    }

    public function findById(int $id): ?Questionnaire
    {
        return $this->repository->findById($id);
    }

    public function softDelete(int $id): void
    {
        $questionnaire = $this->repository->findById($id);
        if ($questionnaire === null) {
            throw new AppException('QUESTIONNAIRE_NOT_FOUND');
        }
        $this->repository->softDelete($questionnaire);
    }

    public function findByResponderRoles(array $roleCodes, ?QuestionnaireQuery $query = null): array
    {
        $roleIds = [];
        foreach ($roleCodes as $code) {
            $role = $this->roleRepository->findByCode($code);
            if ($role !== null) {
                $roleIds[] = $role->getId();
            }
        }

        return $this->repository->findByResponderRoles($roleIds, $query);
    }

    public function list(?QuestionnaireQuery $query = null): array
    {
        return $this->repository->findPaginated($query ?? new QuestionnaireQuery());
    }
}
