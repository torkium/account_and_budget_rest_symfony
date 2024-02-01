<?php

namespace App\Entity;

use App\Enum\PermissionEnum;
use App\Repository\UserBankAccountRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserBankAccountRepository::class)]
class UserBankAccount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["user_bank_account_get"])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(["user_bank_account_get"])]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: BankAccount::class)]
    #[ORM\JoinColumn(name: 'bank_account_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(["user_bank_account_get"])]
    private ?BankAccount $bankAccount = null;

    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(name: 'profile_id', referencedColumnName: 'id', nullable: true)]
    #[Groups(["user_bank_account_get"])]
    private ?Profile $profile = null;

    #[ORM\Column(length: 255)]
    #[Groups(["user_bank_account_get"])]
    private ?PermissionEnum $permissions = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getBankAccount(): ?BankAccount
    {
        return $this->bankAccount;
    }

    public function setBankAccount(?BankAccount $bankAccount): static
    {
        $this->bankAccount = $bankAccount;

        return $this;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function setProfile(?Profile $profile): static
    {
        $this->profile = $profile;

        return $this;
    }

    public function getPermissions(): ?PermissionEnum
    {
        return $this->permissions;
    }

    public function setPermissions(PermissionEnum $permissions): static
    {
        $this->permissions = $permissions;

        return $this;
    }
}
