<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\BankAccount;
use App\Entity\ScheduledTransaction;
use App\Enum\FinancialCategoryTypeEnum;
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

    public function findTransactionsByDateRange(BankAccount $bankAccount, \DateTime $startDate, \DateTime $endDate, ?array $financialCategories = null, ?ScheduledTransaction $scheduledTransaction = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.bankAccount = :bankAccount')
            ->andWhere('t.date >= :startDate')
            ->andWhere('t.date <= :endDate')
            ->setParameter('bankAccount', $bankAccount)
            ->setParameter('startDate', $startDate->format("Y-m-d"))
            ->setParameter('endDate', $endDate->format("Y-m-d"));
        if ($financialCategories) {
            $financialCategoriesIds = array_map(function ($financialCategory) {
                return $financialCategory->getId();
            }, $financialCategories);

            $qb->andWhere($qb->expr()->in('t.financialCategory', $financialCategoriesIds));
        }
        if ($scheduledTransaction) {
            $qb->andWhere('t.scheduledTransaction = :scheduledTransaction')
                ->setParameter('scheduledTransaction', $scheduledTransaction);
        }
        return $qb->orderBy('t.date', 'ASC')->getQuery()->getResult();
    }


    public function getCreditBetweenDate(BankAccount $bankAccount, \DateTime $startDate, \DateTime $endDate, array $financialCategories = null)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('SUM(t.amount)')
            ->where('t.bankAccount = :bankAccount')
            ->andWhere('t.date >= :startDate')
            ->andWhere('t.date <= :endDate')
            ->andWhere('t.amount >= 0')
            ->setParameter('bankAccount', $bankAccount)
            ->setParameter('startDate', $startDate->format("Y-m-d"))
            ->setParameter('endDate', $endDate->format("Y-m-d"));
        if ($financialCategories) {
            $financialCategoriesIds = array_map(function ($financialCategory) {
                return $financialCategory->getId();
            }, $financialCategories);

            $qb->andWhere($qb->expr()->in('t.financialCategory', $financialCategoriesIds));
        }
        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getDebitBetweenDate(BankAccount $bankAccount, \DateTime $startDate, \DateTime $endDate, array $financialCategories = null)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('SUM(t.amount)')
            ->where('t.bankAccount = :bankAccount')
            ->andWhere('t.date >= :startDate')
            ->andWhere('t.date <= :endDate')
            ->andWhere('t.amount < 0')
            ->setParameter('bankAccount', $bankAccount)
            ->setParameter('startDate', $startDate->format("Y-m-d"))
            ->setParameter('endDate', $endDate->format("Y-m-d"));
        if ($financialCategories) {
            $financialCategoriesIds = array_map(function ($financialCategory) {
                return $financialCategory->getId();
            }, $financialCategories);

            $qb->andWhere($qb->expr()->in('t.financialCategory', $financialCategoriesIds));
        }
        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getRealExpensesBetweenDates(BankAccount $bankAccount, \DateTime $startDate, \DateTime $endDate)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('SUM(t.amount) as totalExpenses')
            ->leftJoin('t.financialCategory', 'fc')
            ->where('t.bankAccount = :bankAccount')
            ->andWhere('t.date >= :startDate')
            ->andWhere('t.date <= :endDate')
            ->andWhere('(t.amount < 0 AND t.financialCategory is NULL) OR (t.amount < 0 AND fc.type = :undefinedType) OR fc.type IN (:expenseTypes)')
            ->setParameter('bankAccount', $bankAccount)
            ->setParameter('startDate', $startDate->format("Y-m-d"))
            ->setParameter('endDate', $endDate->format("Y-m-d"))
            ->setParameter('undefinedType', FinancialCategoryTypeEnum::Undefined->value)
            ->setParameter('expenseTypes', [
                FinancialCategoryTypeEnum::EssentialFixedExpense->value,
                FinancialCategoryTypeEnum::EssentialVariableExpense->value,
                FinancialCategoryTypeEnum::NonEssentialFlexibleExpense->value,
            ]);

        return $qb->getQuery()->getSingleScalarResult();
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
