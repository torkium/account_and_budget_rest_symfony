<?php
// src/Security/BankVoter.php

namespace App\Security;

use App\Entity\BankAccount;
use App\Entity\User;
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
            case 'EDIT':
            case 'DELETE':
                return $user === $bank_account->getBank()->getUser();
        }

        return false;
    }
}
