<?php

namespace App\Repository;

use App\Entity\FinancialCategory;
use App\Entity\ScheduledTransaction;
use App\Enum\FinancialCategoryTypeEnum;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
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

    public function findScheduledTransactionsByDateRange(ArrayCollection $bankAccounts, \DateTime $startDate, \DateTime $endDate, array $financialCategories = null)
    {
        $qb = $this->createQueryBuilder('st')
        ->andWhere('st.bankAccount IN (:bankAccountIds)')
        ->andWhere('1=1')
            ->andWhere('(
                (st.startDate <= :startDate AND (st.endDate is NULL OR st.endDate >= :startDate))
                OR
                (st.startDate >= :startDate AND (st.endDate is NULL OR st.startDate <= :endDate))
            )');
        if ($financialCategories) {
            $financialCategoriesIds = array_map(function ($financialCategory) {
                return $financialCategory->getId();
            }, $financialCategories);

            $qb->andWhere($qb->expr()->in('st.financialCategory', $financialCategoriesIds));
        }

        return $qb->setParameter('bankAccountIds', $bankAccounts->map(function ($account) {
            return $account->getId();
        })->toArray())
            ->setParameter('startDate', $startDate->format("Y-m-d"))
            ->setParameter('endDate', $endDate->format("Y-m-d"))
            ->getQuery()
            ->getResult();
    }

    public function findCreditScheduledTransactionsByDateRange(ArrayCollection $bankAccounts, \DateTime $startDate, \DateTime $endDate, array $financialCategories = null)
    {
        $qb = $this->createQueryBuilder('st')
            ->andWhere('st.bankAccount IN (:bankAccountIds)')
            ->andWhere('st.amount >= 0')
            ->andWhere('(
                (st.startDate <= :startDate AND (st.endDate Is NULL OR st.endDate >= :startDate))
                OR
                (st.startDate >= :startDate AND (st.endDate Is NULL OR st.startDate <= :endDate))
            )');
        if ($financialCategories) {
            $financialCategoriesIds = array_map(function ($financialCategory) {
                return $financialCategory->getId();
            }, $financialCategories);

            $qb->andWhere($qb->expr()->in('st.financialCategory', $financialCategoriesIds));
        }

        return $qb->setParameter('bankAccountIds', $bankAccounts->map(function ($account) {
            return $account->getId();
        })->toArray())
            ->setParameter('startDate', $startDate->format("Y-m-d"))
            ->setParameter('endDate', $endDate->format("Y-m-d"))
            ->getQuery()
            ->getResult();
    }

    public function findDebitScheduledTransactionsByDateRange(ArrayCollection $bankAccounts, \DateTime $startDate, \DateTime $endDate, array $financialCategories = null)
    {
        $qb = $this->createQueryBuilder('st')
            ->andWhere('st.bankAccount IN (:bankAccountIds)')
            ->andWhere('st.amount < 0')
            ->andWhere('(
                (st.startDate <= :startDate AND (st.endDate Is NULL OR st.endDate >= :startDate))
                OR
                (st.startDate >= :startDate AND (st.endDate Is NULL OR st.startDate <= :endDate))
            )');
        if ($financialCategories) {
            $financialCategoriesIds = array_map(function ($financialCategory) {
                return $financialCategory->getId();
            }, $financialCategories);

            $qb->andWhere($qb->expr()->in('st.financialCategory', $financialCategoriesIds));
        }

        return $qb->setParameter('bankAccountIds', $bankAccounts->map(function ($account) {
            return $account->getId();
        })->toArray())
            ->setParameter('startDate', $startDate->format("Y-m-d"))
            ->setParameter('endDate', $endDate->format("Y-m-d"))
            ->getQuery()
            ->getResult();
    }

    public function findScheduledTransactions(array $bankAccounts, DateTimeInterface $startDate, DateTimeInterface $endDate, array | null $financialCategories = null, array | null $financialCategoriesType = null, array | null $financialCategoriesTypeToExclude = null, $amountSign = null)
    {
        $qb = $this->createQueryBuilder('st')
            ->leftJoin('st.financialCategory', 'fc')
            ->andWhere('st.bankAccount IN (:bankAccountIds)')
            ->andWhere('st.amount < 0')
            ->andWhere('(
                (st.startDate <= :startDate AND (st.endDate Is NULL OR st.endDate >= :startDate))
                OR
                (st.startDate >= :startDate AND (st.endDate Is NULL OR st.startDate <= :endDate))
            )');
        if ($financialCategories) {
            $financialCategoriesIds = array_map(function ($financialCategory) {
                return $financialCategory->getId();
            }, $financialCategories);

            $qb->andWhere($qb->expr()->in('st.financialCategory', $financialCategoriesIds));
        }

        if ($financialCategoriesType) {
            $qb
                ->andWhere('fc.type IN (:financialCategoriesTypeIds)')
                ->setParameter('financialCategoriesTypeIds', array_map(function (FinancialCategoryTypeEnum $categoryType) {
                    return $categoryType->value;
                }, $financialCategoriesType));
        }
        if ($financialCategories) {
            $qb
                ->andWhere('fc.id IN (:financialCategoryIds)')
                ->setParameter('financialCategoryIds', array_map(function (FinancialCategory $financialCategory) {
                    return $financialCategory->getId();
                }, $financialCategories));
        }

        if ($financialCategoriesTypeToExclude) {
            $qb->andWhere('fc is NULL OR fc.type NOT IN (:financialCategoriesTypeToExcludeIds)')
                ->setParameter('financialCategoriesTypeToExcludeIds', array_map(function (FinancialCategoryTypeEnum $categoryType) {
                    return $categoryType->value;
                }, $financialCategoriesTypeToExclude));
        }

        if ($amountSign === 1) {
            $qb->andWhere('st.amount >= 0');
        } else if ($amountSign === -1) {
            $qb->andWhere('st.amount < 0');
        }

        return $qb->setParameter('bankAccountIds', array_map(function ($account) {
            return $account->getId();
        }, $bankAccounts))
            ->setParameter('startDate', $startDate->format("Y-m-d"))
            ->setParameter('endDate', $endDate->format("Y-m-d"))
            ->getQuery()
            ->getResult();
    }
}
