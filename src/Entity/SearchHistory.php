<?php

namespace App\Entity;

use App\Repository\SearchHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SearchHistoryRepository::class)]
class SearchHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $query = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $searchedAt = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function setQuery(?string $query): static
    {
        $this->query = $query;

        return $this;
    }

    public function getSearchedAt(): ?\DateTimeInterface
    {
        return $this->searchedAt;
    }

    public function setSearchedAt(?\DateTimeInterface $searchedAt): static
    {
        $this->searchedAt = $searchedAt;

        return $this;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function setUser(?string $user): static
    {
        $this->user = $user;

        return $this;
    }
}
