<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\BankAccount;
use App\Entity\FinancialCategory;
use App\Entity\ScheduledTransaction;
use App\Enum\FinancialCategoryTypeEnum;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
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
        return $qb->orderBy('t.date', 'DESC')->getQuery()->getResult();
    }

    /**
     * Trouve les transactions par plage de dates et catégorie financière.
     *
     * @param ArrayCollection<BankAccount> $bankAccounts Comptes bancaires pour le filtrage
     * @param DateTimeInterface $startDate Date de début de la plage
     * @param DateTimeInterface $endDate Date de fin de la plage
     * @param ArrayCollection $financialCategories Type de catégorie financière
     * @param ArrayCollection $categoriesType Type de catégorie financière
     * @return Transaction[] Renvoie un tableau de transactions
     */
    public function findByDateRangeAndCategory(ArrayCollection $bankAccounts, DateTimeInterface $startDate, DateTimeInterface $endDate, ArrayCollection $financialCategories = null, ArrayCollection $categoriesType = null, ArrayCollection $categoriesTypeToExclude = null): array
    {

        $qb = $this->createQueryBuilder('t')
            ->innerJoin('t.financialCategory', 'fc')
            ->where('t.date >= :startDate')
            ->andWhere('t.date <= :endDate')
            ->andWhere('t.bankAccount IN (:bankAccountIds)');
        if ($categoriesType) {
            $qb
                ->andWhere('fc.type IN (:categoryTypeIds)')
                ->setParameter('categoryTypeIds', $categoriesType->map(function (FinancialCategoryTypeEnum $categoryType) {
                    return $categoryType->value;
                })->toArray());
        }
        if ($financialCategories) {
            $qb
                ->andWhere('fc.id IN (:financialCategoryIds)')
                ->setParameter('financialCategoryIds', $financialCategories->map(function (FinancialCategory $financialCategory) {
                    return $financialCategory->getId();
                })->toArray());
        }

        if ($categoriesTypeToExclude) {
            $qb->andWhere('fc is NULL OR fc.type NOT IN (:categoryTypeIds)')
                ->setParameter('categoryTypeIds', $categoriesTypeToExclude->map(function (FinancialCategoryTypeEnum $categoryType) {
                    return $categoryType->value;
                })->toArray());
        }
        $qb
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('bankAccountIds', $bankAccounts->map(function ($account) {
                return $account->getId();
            })->toArray())
            ->orderBy('t.date', 'DESC');

        return $qb->getQuery()->getResult();
    }



    public function getValue(array $bankAccounts, DateTimeInterface | null $startDate = null, DateTimeInterface | null $endDate = null, array | null $financialCategories = null, array | null $financialCategoriesType = null, array | null $financialCategoriesTypeToExclude = null, $amountSign = null)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('SUM(t.amount) as total')
            ->leftJoin('t.financialCategory', 'fc')
            ->where('t.bankAccount IN (:bankAccountIds)');
        if ($startDate) {
            $qb->andWhere('t.date >= :startDate')
            ->setParameter('startDate', $startDate->format("Y-m-d"));
        }
        if ($endDate) {
            $qb->andWhere('t.date <= :endDate')
            ->setParameter('endDate', $endDate->format("Y-m-d"));
        }
        if ($amountSign === 1) {
            $qb->andWhere('t.amount >= 0');
        }
        else if ($amountSign === -1) {
            $qb->andWhere('t.amount < 0');
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
            }, $bankAccounts));

        return $qb->getQuery()->getSingleScalarResult();
    }
    public function getTransactions(array $bankAccounts, DateTimeInterface | null $startDate = null, DateTimeInterface | null $endDate = null, array | null $financialCategories = null, array | null $financialCategoriesType = null, array | null $financialCategoriesTypeToExclude = null, $amountSign = null)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t')
            ->leftJoin('t.financialCategory', 'fc')
            ->where('t.bankAccount IN (:bankAccountIds)');
        if ($startDate) {
            $qb->andWhere('t.date >= :startDate')
            ->setParameter('startDate', $startDate->format("Y-m-d"));
        }
        if ($endDate) {
            $qb->andWhere('t.date <= :endDate')
            ->setParameter('endDate', $endDate->format("Y-m-d"));
        }
        if ($amountSign === 1) {
            $qb->andWhere('t.amount >= 0');
        }
        else if ($amountSign === -1) {
            $qb->andWhere('t.amount < 0');
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
            }, $bankAccounts));

        return $qb->orderBy('t.date', 'DESC')->getQuery()->getResult();
    }

    public function getCreditTransactionsBetweenDates(ArrayCollection $bankAccounts, DateTimeInterface $startDate, DateTimeInterface $endDate, ArrayCollection | null $financialCategories = null, ArrayCollection | null $categoriesType = null, ArrayCollection | null $categoriesTypeToExclude = null)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t')
            ->leftJoin('t.financialCategory', 'fc')
            ->andWhere('t.bankAccount IN (:bankAccountIds)')
            ->andWhere('t.date >= :startDate')
            ->andWhere('t.date <= :endDate')
            ->andWhere('t.amount >= 0');

        if ($categoriesType) {
            $qb
                ->andWhere('fc.type IN (:categoryTypeIds)')
                ->setParameter('categoryTypeIds', $categoriesType->map(function (FinancialCategoryTypeEnum $categoryType) {
                    return $categoryType->value;
                })->toArray());
        }
        if ($financialCategories) {
            $qb
                ->andWhere('fc.id IN (:financialCategoryIds)')
                ->setParameter('financialCategoryIds', $financialCategories->map(function (FinancialCategory $financialCategory) {
                    return $financialCategory->getId();
                })->toArray());
        }

        if ($categoriesTypeToExclude) {
            $qb->andWhere('fc is NULL OR fc.type NOT IN (:categoryTypeIds)')
                ->setParameter('categoryTypeIds', $categoriesTypeToExclude->map(function (FinancialCategoryTypeEnum $categoryType) {
                    return $categoryType->value;
                })->toArray());
        }
        $qb
            ->setParameter('bankAccountIds', $bankAccounts->map(function ($account) {
                return $account->getId();
            })->toArray())
            ->setParameter('startDate', $startDate->format("Y-m-d"))
            ->setParameter('endDate', $endDate->format("Y-m-d"));

        return $qb->orderBy('t.date', 'DESC')->getQuery()->getResult();
    }

    public function getDebitTransactionsBetweenDates(ArrayCollection $bankAccounts, DateTimeInterface $startDate, DateTimeInterface $endDate, ArrayCollection | null $financialCategories = null, ArrayCollection | null $categoriesType = null, ArrayCollection | null $categoriesTypeToExclude = null)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t')
            ->leftJoin('t.financialCategory', 'fc')
            ->andWhere('t.bankAccount IN (:bankAccountIds)')
            ->andWhere('t.date >= :startDate')
            ->andWhere('t.date <= :endDate')
            ->andWhere('t.amount < 0');

        if ($categoriesType) {
            $qb
                ->andWhere('fc.type IN (:categoryTypeIds)')
                ->setParameter('categoryTypeIds', $categoriesType->map(function (FinancialCategoryTypeEnum $categoryType) {
                    return $categoryType->value;
                })->toArray());
        }
        if ($financialCategories) {
            $qb
                ->andWhere('fc.id IN (:financialCategoryIds)')
                ->setParameter('financialCategoryIds', $financialCategories->map(function (FinancialCategory $financialCategory) {
                    return $financialCategory->getId();
                })->toArray());
        }

        if ($categoriesTypeToExclude) {
            $qb->andWhere('fc is NULL OR fc.type NOT IN (:categoryTypeIds)')
                ->setParameter('categoryTypeIds', $categoriesTypeToExclude->map(function (FinancialCategoryTypeEnum $categoryType) {
                    return $categoryType->value;
                })->toArray());
        }
        $qb
            ->setParameter('bankAccountIds', $bankAccounts->map(function ($account) {
                return $account->getId();
            })->toArray())
            ->setParameter('startDate', $startDate->format("Y-m-d"))
            ->setParameter('endDate', $endDate->format("Y-m-d"));

        return $qb->orderBy('t.date', 'DESC')->getQuery()->getResult();
    }

    public function getCreditBetweenDate(BankAccount $bankAccount, ?\DateTime $startDate, ?\DateTime $endDate, array $financialCategories = null)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('SUM(t.amount) as total')
            ->where('t.bankAccount = :bankAccount')
            ->andWhere('t.amount >= 0')
            ->setParameter('bankAccount', $bankAccount);

        if ($startDate) {
            $qb->andWhere('t.date >= :startDate')
                ->setParameter('startDate', $startDate->format("Y-m-d"));
        }
        if ($endDate) {
            $qb->andWhere('t.date <= :endDate')
                ->setParameter('endDate', $endDate->format("Y-m-d"));
        }
        if ($financialCategories) {
            $financialCategoriesIds = array_map(function ($financialCategory) {
                return $financialCategory->getId();
            }, $financialCategories);
            $qb->andWhere($qb->expr()->in('t.financialCategory', $financialCategoriesIds));
        }
        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getDebitBetweenDate(BankAccount $bankAccount, ?\DateTime $startDate, ?\DateTime $endDate, array $financialCategories = null)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('SUM(t.amount) as total')
            ->where('t.bankAccount = :bankAccount')
            ->andWhere('t.amount < 0')
            ->setParameter('bankAccount', $bankAccount);

        if ($startDate) {
            $qb->andWhere('t.date >= :startDate')
                ->setParameter('startDate', $startDate->format("Y-m-d"));
        }
        if ($endDate) {
            $qb->andWhere('t.date <= :endDate')
                ->setParameter('endDate', $endDate->format("Y-m-d"));
        }
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
