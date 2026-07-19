<?php

declare(strict_types=1);

namespace App\Features\Questionnaire\Domain\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'question_options')]
class QuestionOption
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Question::class, inversedBy: 'options')]
    #[ORM\JoinColumn(name: 'question_id', referencedColumnName: 'id', nullable: false)]
    private Question $question;

    #[ORM\Column(name: 'option_text', type: 'string', length: 255)]
    private string $optionText;

    #[ORM\Column(name: 'option_order', type: 'integer', options: ['default' => 0])]
    private int $optionOrder = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function getQuestion(): Question
    {
        return $this->question;
    }

    public function setQuestion(Question $question): void
    {
        $this->question = $question;
    }

    public function getOptionText(): string
    {
        return $this->optionText;
    }

    public function setOptionText(string $optionText): void
    {
        $this->optionText = $optionText;
    }

    public function getOptionOrder(): int
    {
        return $this->optionOrder;
    }

    public function setOptionOrder(int $optionOrder): void
    {
        $this->optionOrder = $optionOrder;
    }
}
