<?php

namespace App\Repository;

use App\Entity\Budget;
use App\Entity\FinancialCategory;
use App\Enum\FinancialCategoryTypeEnum;
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

    public function findBudgetsByDateRange(array $bankAccounts, \DateTime $startDate, \DateTime $endDate, array | null $financialCategories = null, array | null $financialCategoriesType = null, array | null $financialCategoriesTypeToExclude = null, $amountSign = null)
    {
        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.financialCategory', 'fc')
            ->andWhere('b.bankAccount IN (:bankAccountIds)')
            ->andWhere('b.startDate <= :startDate')
            ->andWhere('b.endDate >= :endDate OR b.endDate IS NULL');

            if ($amountSign === 1) {
                $qb->andWhere('b.amount >= 0');
            }
            else if ($amountSign === -1) {
                $qb->andWhere('b.amount < 0');
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
        
        $qb
            ->setParameter('bankAccountIds', array_map(function ($account) {
                return $account->getId();
            }, $bankAccounts))
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);
        return $qb->getQuery()->getResult();
    }
}
