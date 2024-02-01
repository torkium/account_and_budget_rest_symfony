<?php

namespace App\Entity;

use App\Repository\BankRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as Groups;

#[ORM\Entity(repositoryClass: BankRepository::class)]
class Bank
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["bank_get"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["bank_get"])]
    private ?string $label = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }
}
