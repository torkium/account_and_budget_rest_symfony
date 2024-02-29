<?php

namespace App\Entity;

use App\Enum\PermissionEnum;
use App\Repository\BankAccountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\Column(type: 'decimal', scale: 2)]
    #[Groups(["bank_account_get"])]
    private float $initial_amount;

    #[ORM\ManyToOne(inversedBy: 'bank_accounts')]
    #[ORM\JoinColumn(nullable: false, name: 'bank_id', referencedColumnName: 'id')]
    #[Groups(["bank_account_get"])]
    private ?Bank $bank = null;

    #[ORM\OneToMany(mappedBy: 'bankAccount', targetEntity: UserBankAccount::class, cascade: ['persist', 'remove'])]
    private Collection $userBankAccounts;

    public function __construct()
    {
        $this->userBankAccounts = new ArrayCollection();
    }

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

    public function getInitialAmount(): float
    {
        return $this->initial_amount;
    }

    public function setInitialAmount(float $initial_amount): self
    {
        $this->initial_amount = $initial_amount;

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

    public function getUserBankAccounts(): Collection
    {
        return $this->userBankAccounts;
    }

    public function addUserBankAccount(UserBankAccount $userBankAccount): static
    {
        if (!$this->userBankAccounts->contains($userBankAccount)) {
            $this->userBankAccounts->add($userBankAccount);
            $userBankAccount->setBankAccount($this);
        }

        return $this;
    }

    public function removeUserBankAccount(UserBankAccount $userBankAccount): static
    {
        if ($this->userBankAccounts->removeElement($userBankAccount)) {
            if ($userBankAccount->getBankAccount() === $this) {
                $userBankAccount->setBankAccount(null);
            }
        }

        return $this;
    }

    public function hasPermission(User $user, PermissionEnum $permission): bool
    {
        foreach ($this->userBankAccounts as $userBankAccount) {
            if ($userBankAccount->getUser() === $user && $userBankAccount->getPermissions()->value >= $permission->value) {
                return true;
            }
        }
        return false;
    }
}
