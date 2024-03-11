<?php

namespace App\Repository;

use App\Entity\Budget;
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

    public function findBudgetsByDateRange(array $bankAccounts, \DateTime $startDate, \DateTime $endDate)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.bankAccount IN (:bankAccountIds)')
            ->andWhere('b.startDate <= :startDate')
            ->andWhere('b.endDate >= :endDate OR b.endDate IS NULL')
            ->setParameter('bankAccountIds', array_map(function ($account) {
                return $account->getId();
            }, $bankAccounts))
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
    }
}
