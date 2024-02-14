<?php

namespace App\Repository;

use App\Entity\ScheduledTransaction;
use App\Entity\BankAccount;
use App\Entity\FinancialCategory;
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

    public function findScheduledTransactionsByDateRange(BankAccount $bankAccount, \DateTime $startDate, \DateTime $endDate, array $financialCategories = null)
    {
        $qb = $this->createQueryBuilder('st')
            ->andWhere('st.bankAccount = :bankAccount')
            ->andWhere('(
                (st.startDate <= :startDate AND (st.endDate Is NULL OR st.endDate >= :startDate))
                OR
                (st.startDate >= :startDate AND (st.endDate Is NULL OR st.startDate <= :endDate))
            )');
        if($financialCategories){
            $financialCategoriesIds = array_map(function($financialCategory) {
                return $financialCategory->getId();
            }, $financialCategories);

            $qb->andWhere($qb->expr()->in('st.financialCategory', $financialCategoriesIds));
        }

        return $qb->setParameter('bankAccount', $bankAccount)
            ->setParameter('startDate', $startDate->format("Y-m-d"))
            ->setParameter('endDate', $endDate->format("Y-m-d"))
            ->getQuery()
            ->getResult();
    }
}
