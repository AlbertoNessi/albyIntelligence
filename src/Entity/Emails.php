<?php

namespace App\Entity;

use App\Repository\EmailsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmailsRepository::class)]
class Emails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $sender = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $receivers = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSender(): ?string
    {
        return $this->sender;
    }

    public function setSender(?string $sender): static
    {
        $this->sender = $sender;

        return $this;
    }

    public function getReceivers(): ?string
    {
        return $this->receivers;
    }

    public function setReceivers(?string $receivers): static
    {
        $this->receivers = $receivers;

        return $this;
    }
}
