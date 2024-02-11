<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\BankAccount;
use App\Entity\ScheduledTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 *
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function findTransactionsByDateRange(BankAccount $bankAccount, \DateTime $startDate, \DateTime $endDate): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.bankAccount = :bankAccount')
            ->andWhere('t.date >= :startDate')
            ->andWhere('t.date <= :endDate')
            ->setParameter('bankAccount', $bankAccount)
            ->setParameter('startDate', $startDate->format("Y-m-d"))
            ->setParameter('endDate', $endDate->format("Y-m-d"))
            ->orderBy('t.date', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function unsetScheduledTransactionForAll(ScheduledTransaction $scheduledTransaction)
    {
        $qb = $this->createQueryBuilder('t')
            ->update()
            ->set('t.scheduledTransaction', ':null')
            ->where('t.scheduledTransaction = :scheduledTransaction')
            ->setParameter('null', null)
            ->setParameter('scheduledTransaction', $scheduledTransaction)
            ->getQuery();

        return $qb->execute();
    }
}
