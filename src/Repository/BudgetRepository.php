<?php

namespace App\Repository;

use App\Entity\Budget;
use App\Entity\BankAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Budget>
 *
 * @method Budget|null find($id, $lockMode = null, $lockVersion = null)
 * @method Budget|null findOneBy(array $criteria, array $orderBy = null)
 * @method Budget[]    findAll()
 * @method Budget[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BudgetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Budget::class);
    }

    public function findBudgetsByDateRange(BankAccount $bankAccount, \DateTime $startDate, \DateTime $endDate)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.bankAccount = :bankAccount')
            ->andWhere('b.startDate >= :startDate')
            ->andWhere('b.endDate <= :endDate OR b.endDate IS NULL')
            ->setParameter('bankAccount', $bankAccount)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
    }
}
