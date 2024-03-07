<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager)
    {
        $adminUser = new User();
        $adminUser->setUsername('admin');
        $adminUser->setEmail('admin@example.com');

        $adminUser->setPassword($this->passwordHasher->hashPassword(
            $adminUser,
            'password'
        ));
        $adminUser->setRoles(['ROLE_ADMIN']);

        $manager->persist($adminUser);
        $manager->flush();
    }
}
