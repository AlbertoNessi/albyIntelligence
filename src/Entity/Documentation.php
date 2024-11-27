<?php

namespace App\Entity;

use App\Repository\DocumentationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentationRepository::class)]
class Documentation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $context = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $problem_title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $problem_description = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $solution = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function setContext(?string $context): static
    {
        $this->context = $context;

        return $this;
    }

    public function getProblemTitle(): ?string
    {
        return $this->problem_title;
    }

    public function setProblemTitle(?string $problem_title): static
    {
        $this->problem_title = $problem_title;

        return $this;
    }

    public function getProblemDescription(): ?string
    {
        return $this->problem_description;
    }

    public function setProblemDescription(?string $problem_description): static
    {
        $this->problem_description = $problem_description;

        return $this;
    }

    public function getSolution(): ?string
    {
        return $this->solution;
    }

    public function setSolution(?string $solution): static
    {
        $this->solution = $solution;

        return $this;
    }

}
