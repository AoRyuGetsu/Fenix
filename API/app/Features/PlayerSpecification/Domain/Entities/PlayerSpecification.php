<?php

declare(strict_types=1);

namespace App\Features\PlayerSpecification\Domain\Entities;

use App\Features\User\Domain\Entities\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'player_specification')]
class PlayerSpecification
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\Column(name: 'birth_date', type: 'date')]
    private \DateTime $birthDate;

    #[ORM\Column(name: 'height_cm', type: 'decimal', precision: 5, scale: 2)]
    private string $heightCm;

    #[ORM\Column(name: 'weight_kg', type: 'decimal', precision: 5, scale: 2)]
    private string $weightKg;

    #[ORM\Column(type: 'string', length: 20)]
    private string $gender;

    #[ORM\Column(name: 'entry_date', type: 'date')]
    private \DateTime $entryDate;

    #[ORM\Column(name: 'medical_conditions', type: 'text', nullable: true)]
    private ?string $medicalConditions = null;

    #[ORM\Column(name: 'emergency_contact_name', type: 'string', length: 150)]
    private string $emergencyContactName;

    #[ORM\Column(name: 'emergency_contact_phone', type: 'string', length: 20)]
    private string $emergencyContactPhone;

    #[ORM\Column(name: 'emergency_contact_relationship', type: 'string', length: 50)]
    private string $emergencyContactRelationship;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $position = null;

    #[ORM\Column(name: 'jersey_number', type: 'integer', nullable: true)]
    private ?int $jerseyNumber = null;

    #[ORM\Column(name: 'dominant_foot', type: 'string', length: 20, nullable: true)]
    private ?string $dominantFoot = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $nationality = null;

    #[ORM\Column(name: 'blood_type', type: 'string', length: 5, nullable: true)]
    private ?string $bloodType = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $allergies = null;

    #[ORM\Column(name: 'profile_photo_url', type: 'string', length: 500, nullable: true)]
    private ?string $profilePhotoUrl = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'active';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by', referencedColumnName: 'id', nullable: true)]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'updated_by', referencedColumnName: 'id', nullable: true)]
    private ?User $updatedBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'deleted_by', referencedColumnName: 'id', nullable: true)]
    private ?User $deletedBy = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $deletedAt = null;

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getBirthDate(): \DateTime
    {
        return $this->birthDate;
    }

    public function setBirthDate(\DateTime $birthDate): void
    {
        $this->birthDate = $birthDate;
    }

    public function getHeightCm(): string
    {
        return $this->heightCm;
    }

    public function setHeightCm(string $heightCm): void
    {
        $this->heightCm = $heightCm;
    }

    public function getWeightKg(): string
    {
        return $this->weightKg;
    }

    public function setWeightKg(string $weightKg): void
    {
        $this->weightKg = $weightKg;
    }

    public function getGender(): string
    {
        return $this->gender;
    }

    public function setGender(string $gender): void
    {
        $this->gender = $gender;
    }

    public function getEntryDate(): \DateTime
    {
        return $this->entryDate;
    }

    public function setEntryDate(\DateTime $entryDate): void
    {
        $this->entryDate = $entryDate;
    }

    public function getMedicalConditions(): ?string
    {
        return $this->medicalConditions;
    }

    public function setMedicalConditions(?string $medicalConditions): void
    {
        $this->medicalConditions = $medicalConditions;
    }

    public function getEmergencyContactName(): string
    {
        return $this->emergencyContactName;
    }

    public function setEmergencyContactName(string $emergencyContactName): void
    {
        $this->emergencyContactName = $emergencyContactName;
    }

    public function getEmergencyContactPhone(): string
    {
        return $this->emergencyContactPhone;
    }

    public function setEmergencyContactPhone(string $emergencyContactPhone): void
    {
        $this->emergencyContactPhone = $emergencyContactPhone;
    }

    public function getEmergencyContactRelationship(): string
    {
        return $this->emergencyContactRelationship;
    }

    public function setEmergencyContactRelationship(string $emergencyContactRelationship): void
    {
        $this->emergencyContactRelationship = $emergencyContactRelationship;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): void
    {
        $this->position = $position;
    }

    public function getJerseyNumber(): ?int
    {
        return $this->jerseyNumber;
    }

    public function setJerseyNumber(?int $jerseyNumber): void
    {
        $this->jerseyNumber = $jerseyNumber;
    }

    public function getDominantFoot(): ?string
    {
        return $this->dominantFoot;
    }

    public function setDominantFoot(?string $dominantFoot): void
    {
        $this->dominantFoot = $dominantFoot;
    }

    public function getNationality(): ?string
    {
        return $this->nationality;
    }

    public function setNationality(?string $nationality): void
    {
        $this->nationality = $nationality;
    }

    public function getBloodType(): ?string
    {
        return $this->bloodType;
    }

    public function setBloodType(?string $bloodType): void
    {
        $this->bloodType = $bloodType;
    }

    public function getAllergies(): ?string
    {
        return $this->allergies;
    }

    public function setAllergies(?string $allergies): void
    {
        $this->allergies = $allergies;
    }

    public function getProfilePhotoUrl(): ?string
    {
        return $this->profilePhotoUrl;
    }

    public function setProfilePhotoUrl(?string $profilePhotoUrl): void
    {
        $this->profilePhotoUrl = $profilePhotoUrl;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): void
    {
        $this->updatedBy = $updatedBy;
    }

    public function getDeletedBy(): ?User
    {
        return $this->deletedBy;
    }

    public function setDeletedBy(?User $deletedBy): void
    {
        $this->deletedBy = $deletedBy;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getDeletedAt(): ?\DateTime
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTime $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }
}
