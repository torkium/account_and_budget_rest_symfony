<?php

namespace App\Entity;

use App\Enum\FrequencyEnum;
use App\Repository\BudgetRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: BudgetRepository::class)]
class Budget
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(["budget_get"])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(["budget_get"])]
    private ?string $label = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(["budget_get"])]
    private ?float $amount = null;

    #[ORM\Column(type: 'date')]
    #[Groups(["budget_get"])]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Groups(["budget_get"])]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\ManyToOne(targetEntity: FinancialCategory::class)]
    #[Groups(["financial_category_get"])]
    private ?FinancialCategory $financialCategory = null;

    #[ORM\ManyToOne(targetEntity: BankAccount::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["bank_account_get"])]
    private ?BankAccount $bankAccount = null;

    #[ORM\Column(type: 'string', enumType: FrequencyEnum::class)]
    #[Groups(["budget_get"])]
    private FrequencyEnum $frequency;

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;
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

    public function getBankAccount(): ?BankAccount
    {
        return $this->bankAccount;
    }

    public function setBankAccount(?BankAccount $bankAccount): self
    {
        $this->bankAccount = $bankAccount;
        return $this;
    }

    public function getFrequency(): FrequencyEnum
    {
        return $this->frequency;
    }

    public function setFrequency(FrequencyEnum $frequency): self
    {
        $this->frequency = $frequency;
        return $this;
    }
}
