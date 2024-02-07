<?php
// src/Security/TransactionVoter.php

namespace App\Security;

use App\Entity\Transaction;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TransactionVoter extends Voter
{
    private $tokenStorage;
    private $authorizationChecker;

    public function __construct(TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
    }

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, ['VIEW', 'EDIT', 'DELETE']) && $subject instanceof Transaction;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $this->tokenStorage->getToken()->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Transaction $transaction */
        $transaction = $subject;
        $bankAccount = $transaction->getBankAccount();

        // Directly use BankAccountVoter logic for permissions check
        switch ($attribute) {
            case 'VIEW':
                return $this->authorizationChecker->isGranted('VIEW', $bankAccount);
            case 'EDIT':
                return $this->authorizationChecker->isGranted('EDIT', $bankAccount);
            case 'DELETE':
                return $this->authorizationChecker->isGranted('DELETE', $bankAccount);
        }

        return false;
    }
}
