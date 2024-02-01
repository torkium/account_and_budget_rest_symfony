<?php

namespace App\Repository;

use App\Entity\UserBankAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Profile>
 *
 * @method UserBankAccount|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserBankAccount|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserBankAccount[]    findAll()
 * @method UserBankAccount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserBankAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBankAccount::class);
    }
}
