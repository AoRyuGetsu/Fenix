<?php

declare(strict_types=1);

namespace App\Features\Teams\Domain\Entities;

use App\Features\User\Domain\Entities\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'teams')]
class Team
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    private int $id;

    #[ORM\Column(type: 'string', length: 150)]
    private string $name;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $code;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'trainer_user_id', referencedColumnName: 'id', nullable: false)]
    private User $trainer;

    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(
        name: 'team_players',
        joinColumns: new ORM\JoinColumn(name: 'team_id', referencedColumnName: 'id'),
        inverseJoinColumns: new ORM\JoinColumn(name: 'player_user_id', referencedColumnName: 'id'),
    )]
    private Collection $players;

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

    public function __construct()
    {
        $this->players = new ArrayCollection();
    }

    public function getId(): int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }

    public function getCode(): string { return $this->code; }
    public function setCode(string $code): void { $this->code = $code; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): void { $this->description = $description; }

    public function getTrainer(): User { return $this->trainer; }
    public function setTrainer(User $trainer): void { $this->trainer = $trainer; }

    public function getPlayers(): Collection { return $this->players; }
    public function addPlayer(User $player): void
    {
        if (!$this->players->contains($player)) {
            $this->players->add($player);
        }
    }
    public function removePlayer(User $player): void { $this->players->removeElement($player); }
    public function setPlayers(array $players): void
    {
        $this->players->clear();
        foreach ($players as $p) {
            $this->addPlayer($p);
        }
    }

    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): void { $this->createdBy = $createdBy; }

    public function getUpdatedBy(): ?User { return $this->updatedBy; }
    public function setUpdatedBy(?User $updatedBy): void { $this->updatedBy = $updatedBy; }

    public function getDeletedBy(): ?User { return $this->deletedBy; }
    public function setDeletedBy(?User $deletedBy): void { $this->deletedBy = $deletedBy; }

    public function getCreatedAt(): \DateTime { return $this->createdAt; }
    public function setCreatedAt(\DateTime $createdAt): void { $this->createdAt = $createdAt; }

    public function getUpdatedAt(): \DateTime { return $this->updatedAt; }
    public function setUpdatedAt(\DateTime $updatedAt): void { $this->updatedAt = $updatedAt; }

    public function getDeletedAt(): ?\DateTime { return $this->deletedAt; }
    public function setDeletedAt(?\DateTime $deletedAt): void { $this->deletedAt = $deletedAt; }
}
