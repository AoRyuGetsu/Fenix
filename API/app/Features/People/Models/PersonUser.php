<?php

declare(strict_types=1);

namespace App\Features\People\Models;

use App\Features\User\Models\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'person_user')]
class PersonUser
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: Person::class, inversedBy: 'personUser')]
    #[ORM\JoinColumn(name: 'person_id', referencedColumnName: 'id')]
    private Person $person;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', unique: true)]
    private User $user;

    public function getPerson(): Person
    {
        return $this->person;
    }

    public function setPerson(Person $person): void
    {
        $this->person = $person;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}
