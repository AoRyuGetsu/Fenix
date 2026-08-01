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
    private const CATEGORIES = ['SUB10', 'SUB12', 'SUB14', 'SUB16', 'LIBRE'];
    private const SHIRT_SIZES = ['XS', 'S', 'M', 'L', 'XL'];

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
        $spec->setJerseyNumber1(isset($data['jersey_number_1']) ? (int) $data['jersey_number_1'] : null);
        $spec->setJerseyNumber2(isset($data['jersey_number_2']) ? (int) $data['jersey_number_2'] : null);
        $spec->setJerseyNumber3(isset($data['jersey_number_3']) ? (int) $data['jersey_number_3'] : null);
        $spec->setDominantFoot($data['dominant_foot'] ?? null);
        $spec->setNationality($data['nationality'] ?? null);
        $spec->setBloodType($data['blood_type'] ?? null);
        $spec->setAllergies($data['allergies'] ?? null);
        $spec->setInsurance($data['insurance'] ?? null);
        $spec->setMedications($data['medications'] ?? null);
        $spec->setBasketballExperience(isset($data['basketball_experience']) ? (bool) $data['basketball_experience'] : null);
        $spec->setPreviousClub($data['previous_club'] ?? null);
        if (isset($data['category'])) {
            $this->assertCategory($data['category']);
            $spec->setCategory($data['category']);
        }
        if (isset($data['shirt_size'])) {
            $this->assertShirtSize($data['shirt_size']);
            $spec->setShirtSize($data['shirt_size']);
        }
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
        if (array_key_exists('jersey_number_1', $data)) {
            $spec->setJerseyNumber1($data['jersey_number_1'] !== null ? (int) $data['jersey_number_1'] : null);
        }
        if (array_key_exists('jersey_number_2', $data)) {
            $spec->setJerseyNumber2($data['jersey_number_2'] !== null ? (int) $data['jersey_number_2'] : null);
        }
        if (array_key_exists('jersey_number_3', $data)) {
            $spec->setJerseyNumber3($data['jersey_number_3'] !== null ? (int) $data['jersey_number_3'] : null);
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
        if (array_key_exists('insurance', $data)) {
            $spec->setInsurance($data['insurance']);
        }
        if (array_key_exists('medications', $data)) {
            $spec->setMedications($data['medications']);
        }
        if (array_key_exists('basketball_experience', $data)) {
            $spec->setBasketballExperience($data['basketball_experience'] !== null ? (bool) $data['basketball_experience'] : null);
        }
        if (array_key_exists('previous_club', $data)) {
            $spec->setPreviousClub($data['previous_club']);
        }
        if (array_key_exists('category', $data)) {
            if ($data['category'] !== null) {
                $this->assertCategory($data['category']);
            }
            $spec->setCategory($data['category']);
        }
        if (array_key_exists('shirt_size', $data)) {
            if ($data['shirt_size'] !== null) {
                $this->assertShirtSize($data['shirt_size']);
            }
            $spec->setShirtSize($data['shirt_size']);
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

    private function assertCategory(string $category): void
    {
        if (!in_array($category, self::CATEGORIES, true)) {
            throw new AppException('PLAYER_SPEC_INVALID_CATEGORY');
        }
    }

    private function assertShirtSize(string $shirtSize): void
    {
        if (!in_array($shirtSize, self::SHIRT_SIZES, true)) {
            throw new AppException('PLAYER_SPEC_INVALID_SHIRT_SIZE');
        }
    }
}
