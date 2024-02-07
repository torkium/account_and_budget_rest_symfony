<?php

namespace App\Security;

use App\Entity\ScheduledTransaction;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ScheduledTransactionVoter extends Voter
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
        return in_array($attribute, ['VIEW', 'EDIT', 'DELETE']) && $subject instanceof ScheduledTransaction;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $this->tokenStorage->getToken()->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var ScheduledTransaction $scheduledTransaction */
        $scheduledTransaction = $subject;
        $bankAccount = $scheduledTransaction->getBankAccount();

        // Reuse BankAccountVoter logic for permissions check
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
