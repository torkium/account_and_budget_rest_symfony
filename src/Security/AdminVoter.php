<?php
// src/Security/ProfileVoter.php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AdminVoter extends Voter
{
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === 'ROLE_ADMIN';
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /* @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        if (!$user instanceof User) {
            return false;
        }
        return $user->hasRole('ROLE_ADMIN');
    }
}
