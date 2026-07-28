<?php

declare(strict_types=1);

namespace App\Features\PlayerSpecification\Application\Services;

use App\Core\AppException;
use App\Features\PlayerSpecification\Application\DTO\PlayerSpecificationQuery;
use App\Features\PlayerSpecification\Domain\Entities\PlayerSpecification;
use App\Features\PlayerSpecification\Domain\Interfaces\PlayerSpecificationRepositoryInterface;
use App\Features\PlayerSpecification\Domain\Interfaces\PlayerSpecificationServiceInterface;
use App\Features\RolesPermisos\Domain\Interfaces\RoleAccessControlServiceInterface;
use App\Features\User\Domain\Interfaces\UserServiceInterface;

class PlayerSpecificationService implements PlayerSpecificationServiceInterface
{
    public function __construct(
        private PlayerSpecificationRepositoryInterface $repository,
        private UserServiceInterface $userService,
        private RoleAccessControlServiceInterface $roleService,
    ) {}

    public function findById(int $id): ?PlayerSpecification
    {
        return $this->repository->findById($id);
    }

    public function findPaginated(PlayerSpecificationQuery $query): array
    {
        return $this->repository->findPaginated($query);
    }

    public function create(int $userId, array $data, int $authUserId): PlayerSpecification
    {
        $user = $this->userService->findById($userId);
        if ($user === null) {
            throw new AppException('USER_NOT_FOUND');
        }

        if (!$this->roleService->hasRole($userId, 'JUG')) {
            throw new AppException('PLAYER_SPEC_USER_NOT_JUG');
        }

        $existing = $this->repository->findByUserId($userId);
        if ($existing !== null) {
            throw new AppException('PLAYER_SPEC_ALREADY_EXISTS');
        }

        $spec = new PlayerSpecification();
        $spec->setUser($user);
        $spec->setBirthDate(new \DateTime($data['birth_date']));
        $spec->setHeightCm((string) $data['height_cm']);
        $spec->setWeightKg((string) $data['weight_kg']);
        $spec->setGender($data['gender']);
        $spec->setEntryDate(new \DateTime($data['entry_date']));
        $spec->setMedicalConditions($data['medical_conditions'] ?? null);
        $spec->setEmergencyContactName($data['emergency_contact_name']);
        $spec->setEmergencyContactPhone($data['emergency_contact_phone']);
        $spec->setEmergencyContactRelationship($data['emergency_contact_relationship']);
        $spec->setPosition($data['position'] ?? null);
        $spec->setJerseyNumber(isset($data['jersey_number']) ? (int) $data['jersey_number'] : null);
        $spec->setDominantFoot($data['dominant_foot'] ?? null);
        $spec->setNationality($data['nationality'] ?? null);
        $spec->setBloodType($data['blood_type'] ?? null);
        $spec->setAllergies($data['allergies'] ?? null);
        $spec->setProfilePhotoUrl($data['profile_photo_url'] ?? null);
        $spec->setStatus($data['status'] ?? 'active');
        $spec->setCreatedAt(new \DateTime());
        $spec->setUpdatedAt(new \DateTime());

        $authUser = $this->userService->findById($authUserId);
        $spec->setCreatedBy($authUser);

        $this->repository->save($spec);

        return $spec;
    }

    public function update(int $userId, array $data, int $authUserId): PlayerSpecification
    {
        $spec = $this->repository->findByUserId($userId);
        if ($spec === null) {
            throw new AppException('PLAYER_SPEC_NOT_FOUND');
        }

        if (isset($data['birth_date'])) {
            $spec->setBirthDate(new \DateTime($data['birth_date']));
        }
        if (isset($data['height_cm'])) {
            $spec->setHeightCm((string) $data['height_cm']);
        }
        if (isset($data['weight_kg'])) {
            $spec->setWeightKg((string) $data['weight_kg']);
        }
        if (isset($data['gender'])) {
            $spec->setGender($data['gender']);
        }
        if (isset($data['entry_date'])) {
            $spec->setEntryDate(new \DateTime($data['entry_date']));
        }
        if (array_key_exists('medical_conditions', $data)) {
            $spec->setMedicalConditions($data['medical_conditions']);
        }
        if (isset($data['emergency_contact_name'])) {
            $spec->setEmergencyContactName($data['emergency_contact_name']);
        }
        if (isset($data['emergency_contact_phone'])) {
            $spec->setEmergencyContactPhone($data['emergency_contact_phone']);
        }
        if (isset($data['emergency_contact_relationship'])) {
            $spec->setEmergencyContactRelationship($data['emergency_contact_relationship']);
        }
        if (array_key_exists('position', $data)) {
            $spec->setPosition($data['position']);
        }
        if (array_key_exists('jersey_number', $data)) {
            $spec->setJerseyNumber($data['jersey_number'] !== null ? (int) $data['jersey_number'] : null);
        }
        if (array_key_exists('dominant_foot', $data)) {
            $spec->setDominantFoot($data['dominant_foot']);
        }
        if (array_key_exists('nationality', $data)) {
            $spec->setNationality($data['nationality']);
        }
        if (array_key_exists('blood_type', $data)) {
            $spec->setBloodType($data['blood_type']);
        }
        if (array_key_exists('allergies', $data)) {
            $spec->setAllergies($data['allergies']);
        }
        if (array_key_exists('profile_photo_url', $data)) {
            $spec->setProfilePhotoUrl($data['profile_photo_url']);
        }
        if (isset($data['status'])) {
            $spec->setStatus($data['status']);
        }

        $spec->setUpdatedAt(new \DateTime());

        $authUser = $this->userService->findById($authUserId);
        $spec->setUpdatedBy($authUser);

        $this->repository->save($spec);

        return $spec;
    }

    public function softDelete(int $userId): void
    {
        $spec = $this->repository->findByUserId($userId);
        if ($spec === null) {
            throw new AppException('PLAYER_SPEC_NOT_FOUND');
        }

        $this->repository->softDelete($spec);
    }
}
