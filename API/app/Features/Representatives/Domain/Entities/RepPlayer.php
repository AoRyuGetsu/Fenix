<?php

declare(strict_types=1);

namespace App\Features\Representatives\Domain\Entities;

use App\Features\User\Domain\Entities\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'rep_players')]
class RepPlayer
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'rep_user_id', referencedColumnName: 'id', nullable: false)]
    private User $rep;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'player_user_id', referencedColumnName: 'id', nullable: false)]
    private User $player;

    public function __construct(User $rep, User $player)
    {
        $this->rep = $rep;
        $this->player = $player;
    }

    public function getRep(): User
    {
        return $this->rep;
    }

    public function getPlayer(): User
    {
        return $this->player;
    }
}
