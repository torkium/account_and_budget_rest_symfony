<?php

namespace App\Entity;

use App\Repository\BankAccountRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as Groups;

#[ORM\Entity(repositoryClass: BankAccountRepository::class)]
class BankAccount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["bank_account_get"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["bank_account_get"])]
    private ?string $label = null;

    #[ORM\Column(length: 255)]
    #[Groups(["bank_account_get"])]
    private ?string $account_number = null;

    #[ORM\ManyToOne(inversedBy: 'bank_accounts')]
    #[ORM\JoinColumn(nullable: false, name: 'bank_id', referencedColumnName: 'id')]
    #[Groups(["bank_account_get"])]
    private ?Bank $bank = null;

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

    public function getAccountNumber(): ?string
    {
        return $this->account_number;
    }

    public function setAccountNumber(string $account_number): static
    {
        $this->account_number = $account_number;

        return $this;
    }

    public function getBank(): ?Bank
    {
        return $this->bank;
    }

    public function setBank(?Bank $bank): static
    {
        $this->bank = $bank;

        return $this;
    }
}
