<?php
// src/Security/BankVoter.php

namespace App\Security;

use App\Entity\BankAccount;
use App\Entity\User;
use App\Enum\PermissionEnum;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class BankAccountVoter extends Voter
{
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, ['VIEW', 'EDIT', 'DELETE']) && $subject instanceof BankAccount;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $this->tokenStorage->getToken()->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var BankAccount $bank_account */
        $bank_account = $subject;

        switch ($attribute) {
            case 'VIEW':
                return $bank_account->hasPermission($user, PermissionEnum::READER);
            case 'EDIT':
                return $bank_account->hasPermission($user, PermissionEnum::WRITER);
            case 'DELETE':
                return $bank_account->hasPermission($user, PermissionEnum::ADMIN);
        }

        return false;
    }
}
