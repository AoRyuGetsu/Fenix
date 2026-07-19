<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Domain\Entities;

use App\Features\Questionnaire\Domain\Entities\Section;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'questions')]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Section::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(name: 'section_id', referencedColumnName: 'id', nullable: false)]
    private Section $section;

    #[ORM\Column(type: 'text')]
    private string $text;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(name: 'question_order', type: 'integer', options: ['default' => 0])]
    private int $questionOrder = 0;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $deletedAt = null;

    #[ORM\OneToMany(mappedBy: 'question', targetEntity: QuestionOption::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $options;

    public function __construct()
    {
        $this->options = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSection(): Section
    {
        return $this->section;
    }

    public function setSection(Section $section): void
    {
        $this->section = $section;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getQuestionOrder(): int
    {
        return $this->questionOrder;
    }

    public function setQuestionOrder(int $questionOrder): void
    {
        $this->questionOrder = $questionOrder;
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

    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function addOption(QuestionOption $option): void
    {
        if (!$this->options->contains($option)) {
            $this->options->add($option);
            $option->setQuestion($this);
        }
    }

    public function removeOption(QuestionOption $option): void
    {
        $this->options->removeElement($option);
    }
}
