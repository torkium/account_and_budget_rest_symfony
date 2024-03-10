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

        $balanceAtDate = bcadd($bankAccount->getInitialAmount(),($totalTransactions ? $totalTransactions : 0), 2);

        return $balanceAtDate;
    }

    

    public function getTotalTransactions(BankAccount $bankAccount, \DateTime $date=null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        
        $qb->select('SUM(t.amount) as totalTransactions')
            ->from('App\Entity\Transaction', 't')
            ->where('t.bankAccount = :bankAccount');
        if($date){
            $qb->andWhere('t.date < :date')
            ->setParameter('date', $date);
        }
        $qb
            ->setParameter('bankAccount', $bankAccount);

        $totalTransactions = $qb->getQuery()->getSingleScalarResult();

        $balanceAtDate = $totalTransactions ? $totalTransactions : 0;

        return $balanceAtDate;
    }
}
