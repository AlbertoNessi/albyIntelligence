<?php

namespace App\Entity;

use App\Repository\LastIndexUpdateRepository;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Boolean;

#[ORM\Entity(repositoryClass: LastIndexUpdateRepository::class)]
class LastIndexUpdate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isLast = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getIsLast(): ?bool
    {
        return $this->isLast;
    }

    public function setIsLast(?bool $isLast): static
    {
        $this->isLast = $isLast;

        return $this;
    }

}
