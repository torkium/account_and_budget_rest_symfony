<?php

namespace App\Repository;

use App\Entity\BankAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Profile>
 *
 * @method BankAccount|null find($id, $lockMode = null, $lockVersion = null)
 * @method BankAccount|null findOneBy(array $criteria, array $orderBy = null)
 * @method BankAccount[]    findAll()
 * @method BankAccount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BankAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BankAccount::class);
    }

    

    public function getBalanceAtDate(BankAccount $bankAccount, \DateTime $date)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        
        $qb->select('SUM(t.amount) as totalTransactions')
            ->from('App\Entity\Transaction', 't')
            ->where('t.bankAccount = :bankAccount')
            ->andWhere('t.date < :date')
            ->setParameter('bankAccount', $bankAccount)
            ->setParameter('date', $date);

        $totalTransactions = $qb->getQuery()->getSingleScalarResult();

        $balanceAtDate = $bankAccount->getInitialAmount() + ($totalTransactions ? $totalTransactions : 0);

        return $balanceAtDate;
    }
}
