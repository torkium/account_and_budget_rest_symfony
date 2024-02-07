<?php

namespace App\Repository;

use App\Entity\ScheduledTransaction;
use App\Entity\BankAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ScheduledTransaction>
 *
 * @method ScheduledTransaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method ScheduledTransaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method ScheduledTransaction[]    findAll()
 * @method ScheduledTransaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScheduledTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScheduledTransaction::class);
    }

    public function findScheduledTransactionsByDateRange(BankAccount $bankAccount, \DateTime $startDate, \DateTime $endDate)
    {
        return $this->createQueryBuilder('st')
            ->andWhere('st.bankAccount = :bankAccount')
            ->andWhere('st.startDate >= :startDate')
            ->andWhere('st.endDate <= :endDate OR st.endDate IS NULL')
            ->setParameter('bankAccount', $bankAccount)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
    }
}
