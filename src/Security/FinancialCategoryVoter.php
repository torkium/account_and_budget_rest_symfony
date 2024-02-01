<?php
// src/Security/BankVoter.php

namespace App\Security;

use App\Entity\FinancialCategory;
use App\Entity\User;
use App\Enum\PermissionEnum;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class FinancialCategoryVoter extends Voter
{
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, ['VIEW', 'EDIT', 'DELETE']) && $subject instanceof FinancialCategory;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $this->tokenStorage->getToken()->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var FinancialCategory $financial_category */
        $financial_category = $subject;

        switch ($attribute) {
            case 'VIEW':
                return (!$financial_category->getUser() || $financial_category->getUser() === $user);
            case 'EDIT':
            case 'DELETE':
                return ((!$financial_category->getUser() && $user->hasRole('ROLE_ADMIN')) || $financial_category->getUser() === $user);
        }

        return false;
    }
}
