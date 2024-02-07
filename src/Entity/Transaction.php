<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(["transaction_get"])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(["transaction_get"])]
    private ?string $reference = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(["transaction_get"])]
    private string $label;

    #[ORM\Column(type: 'decimal', scale: 2)]
    #[Groups(["transaction_get"])]
    private float $amount;

    #[ORM\Column(type: 'datetime')]
    #[Groups(["transaction_get"])]
    private \DateTimeInterface $date;

    #[ORM\ManyToOne(targetEntity: FinancialCategory::class, inversedBy: 'transactions')]
    #[Groups(["financial_category_get"])]
    private ?FinancialCategory $financialCategory = null;

    #[ORM\ManyToOne(targetEntity: BankAccount::class, inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["bank_account_get"])]
    private BankAccount $bankAccount;

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getFinancialCategory(): ?FinancialCategory
    {
        return $this->financialCategory;
    }

    public function setFinancialCategory(?FinancialCategory $financialCategory): self
    {
        $this->financialCategory = $financialCategory;

        return $this;
    }

    public function getBankAccount(): BankAccount
    {
        return $this->bankAccount;
    }

    public function setBankAccount(BankAccount $bankAccount): self
    {
        $this->bankAccount = $bankAccount;

        return $this;
    }
}
