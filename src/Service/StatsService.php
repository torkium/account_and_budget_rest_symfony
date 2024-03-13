<?php

namespace App\Service;

use App\DTO\BudgetSummary;
use App\DTO\Stats\AnnualValueForMonth;
use App\Entity\FinancialCategory;
use App\Enum\FinancialCategoryTypeEnum;
use App\Repository\BankAccountRepository;
use App\Repository\BudgetRepository;
use App\Repository\TransactionRepository;
use App\Repository\ScheduledTransactionRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;

class StatsService
{
    private TransactionRepository $transactionRepository;
    private ScheduledTransactionRepository $scheduledTransactionRepository;
    private BankAccountRepository $bankAccountRepository;
    private BudgetRepository $budgetRepository;
    private ScheduledTransactionService $scheduledTransactionService;
    private FinancialCategoryService $financialCategoryService;
    private BudgetService $budgetService;

    public function __construct(
        TransactionRepository $transactionRepository,
        ScheduledTransactionRepository $scheduledTransactionRepository,
        BankAccountRepository $bankAccountRepository,
        BudgetRepository $budgetRepository,
        ScheduledTransactionService $scheduledTransactionService,
        FinancialCategoryService $financialCategoryService,
        BudgetService $budgetService,
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->scheduledTransactionRepository = $scheduledTransactionRepository;
        $this->bankAccountRepository = $bankAccountRepository;
        $this->budgetRepository = $budgetRepository;
        $this->scheduledTransactionService = $scheduledTransactionService;
        $this->financialCategoryService = $financialCategoryService;
        $this->budgetService = $budgetService;
    }

    public function getAnnualIncomesByMonth(ArrayCollection $bankAccounts, \DateTime $startDate, \DateTime $endDate): array
    {

        $transactions = $this->transactionRepository->getCreditTransactionsBetweenDates(
            $bankAccounts,
            $startDate,
            $endDate,
            null,
            null,
            count($bankAccounts) === 1 ? null : new ArrayCollection([FinancialCategoryTypeEnum::Internal])
        );

        $scheduledTransactions = $this->scheduledTransactionRepository->findScheduledTransactions(
            $bankAccounts->toArray(),
            $startDate,
            $endDate,
            null,
            null,
            count($bankAccounts) === 1 ? null : [FinancialCategoryTypeEnum::Internal],
            1
        );
        return $this->getValuesByMonth($startDate, $endDate, $transactions, $scheduledTransactions);
    }

    public function getAnnualExpensesByMonth(ArrayCollection $bankAccounts, \DateTime $startDate, \DateTime $endDate): array
    {
        $transactions = $this->transactionRepository->getDebitTransactionsBetweenDates(
            $bankAccounts,
            $startDate,
            $endDate,
            null,
            null,
            count($bankAccounts) === 1 ? null : new ArrayCollection([FinancialCategoryTypeEnum::Internal])
        );

        $budgets = $this->budgetRepository->findBudgetsByDateRange($bankAccounts->toArray(), $startDate, $endDate);
        $scheduledTransactions = $this->scheduledTransactionRepository->findScheduledTransactions(
            $bankAccounts->toArray(),
            $startDate,
            $endDate,
            null,
            null,
            count($bankAccounts) === 1 ? null : [FinancialCategoryTypeEnum::Internal],
            -1
        );
        return $this->getValuesByMonth($startDate, $endDate, $transactions, $scheduledTransactions, $budgets);
    }

    private function getValuesByMonth(\DateTime $startDate, \DateTime $endDate, array $transactions = [], array $scheduledTransactions = [], array $budgets = [])
    {
        $annualValueByMonth = [];

        $predictedTransactions = $this->scheduledTransactionService->generatePredictedTransactions($scheduledTransactions, $startDate, $endDate);

        $transactions = array_merge($transactions, $predictedTransactions);

        $monthlyValue = [];

        foreach ($transactions as $transaction) {
            $month = $transaction->getDate()->format('Y-m');

            if (!isset($monthlyValue[$month])) {
                $monthlyValue[$month] = 0;
            }

            $monthlyValue[$month] = bcadd($monthlyValue[$month], $transaction->getAmount(), 2);
        }

        foreach ($monthlyValue as $month => $amount) {
            $monthStart = new \DateTime($month . "-01");
            $monthEnd = new \DateTime($monthStart->format("Y-m-t"));
            foreach ($budgets as $budget) {
                /** @var BudgetSummary $budgetSummary */
                $budgetSummary = $this->budgetService->calculateBudgetSummary($budget, $monthStart, $monthEnd);
                if ($budgetSummary->summary > 0) {
                    $amount = bcsub($amount, $budgetSummary->summary, 2);
                }
            }
            $annualValueByMonth[] = new AnnualValueForMonth($amount, $month);
        }

        usort($annualValueByMonth, function ($a, $b) {
            return $a->month <=> $b->month;
        });
        return $annualValueByMonth;
    }

    public function getAnnualValuesByCategoryByMonth(ArrayCollection $bankAccounts, \DateTime $startDate, \DateTime $endDate, FinancialCategory $rootFinancialCategory = null): array
    {
        $results = [];

        $period = new \DatePeriod($startDate, new \DateInterval('P1M'), $endDate);
        $financialCategories = $this->financialCategoryService->getAllAccessibleFinancialCategoriesFlat($rootFinancialCategory);
        $scheduledTransactions = $this->scheduledTransactionRepository->findScheduledTransactionsByDateRange($bankAccounts, $startDate, $endDate, $financialCategories);

        foreach ($period as $date) {
            $monthStart = new \DateTime($date->format("Y-m-01"));
            $monthEnd = new \DateTime($date->format("Y-m-t"));

            $transactions = $this->transactionRepository->findByDateRangeAndCategory(
                $bankAccounts,
                $monthStart,
                $monthEnd,
                new ArrayCollection($financialCategories),
                null,
                count($bankAccounts) === 1 ? null : new ArrayCollection([FinancialCategoryTypeEnum::Internal])
            );
            $predictedTransactions = $this->scheduledTransactionService->generatePredictedTransactions($scheduledTransactions, $monthStart, $monthEnd);
            $transactions = array_merge($transactions, $predictedTransactions);

            $monthlyData = [];
            foreach ($transactions as $transaction) {
                $category = $transaction->getFinancialCategory();
                $rootCategory = $category ? $transaction->getFinancialCategory()->getRootParent($rootFinancialCategory) : null;
                $categoryLabel = $rootCategory ? $rootCategory->getLabel() : null;
                $categoryLabel = $categoryLabel ? $categoryLabel : $category->getLabel();
                if (!isset($monthlyData[$categoryLabel])) {
                    $monthlyData[$categoryLabel] = 0;
                }
                $monthlyData[$categoryLabel] = bcadd($monthlyData[$categoryLabel], $transaction->getAmount(), 2);
            }

            $dataEntries = [];
            foreach ($monthlyData as $category => $amount) {
                $dataEntries[] = [
                    'category' => $category,
                    'amount' => $amount
                ];
            }

            $results[] = [
                'datas' => $dataEntries,
                'month' => $date->format("Y-m")
            ];
        }
        usort($results, function ($a, $b) {
            return $a['month'] <=> $b['month'];
        });
        return $results;
    }

    public function getAnnualExpensesByCategoryByMonth(ArrayCollection $bankAccounts, \DateTime $startDate, \DateTime $endDate, FinancialCategory $rootFinancialCategory = null): array
    {
        $results = [];

        $period = new \DatePeriod($startDate, new \DateInterval('P1M'), $endDate);
        $financialCategories = $this->financialCategoryService->getAllAccessibleFinancialCategoriesFlat($rootFinancialCategory);
        $firstDayOfCurrentMonth = new DateTime('first day of this month');
        $firstDayOfCurrentMonth->setTime(0, 0, 0);
        foreach ($period as $date) {
            $monthStart = new \DateTime($date->format("Y-m-01"));
            $monthEnd = new \DateTime($date->format("Y-m-t"));

            $transactions = $this->transactionRepository->getTransactions(
                $bankAccounts->toArray(),
                $monthStart,
                $monthEnd,
                $financialCategories,
                FinancialCategoryTypeEnum::expenseTypes(),
                null,
                0
            );

            $scheduledTransactions = $this->scheduledTransactionRepository->findScheduledTransactions(
                $bankAccounts->toArray(),
                $startDate,
                $endDate,
                $financialCategories,
                FinancialCategoryTypeEnum::expenseTypes(),
                null,
                0
            );
            $predictedTransactions = $this->scheduledTransactionService->generatePredictedTransactions($scheduledTransactions, $monthStart, $monthEnd);
            $transactions = array_merge($transactions, $predictedTransactions);

            $monthlyData = [];
            foreach ($transactions as $transaction) {
                $category = $transaction->getFinancialCategory();
                $rootCategory = $category ? $transaction->getFinancialCategory()->getRootParent($rootFinancialCategory) : null;
                $categoryLabel = $rootCategory ? $rootCategory->getLabel() : null;
                $categoryLabel = $categoryLabel ? $categoryLabel : $category->getLabel();
                if (!isset($monthlyData[$categoryLabel])) {
                    $monthlyData[$categoryLabel] = 0;
                }
                $monthlyData[$categoryLabel] = bcadd($monthlyData[$categoryLabel], $transaction->getAmount(), 2);
            }

            $dataEntries = [];
            if ($endDate > $firstDayOfCurrentMonth) {
                if ($startDate < $firstDayOfCurrentMonth) {
                    $startDate = $firstDayOfCurrentMonth;
                }
                foreach ($bankAccounts as $bankAccount) {
                    /** @var BudgetSummary[] $budgetSummaries */
                    $budgetSummaries = $this->budgetService->calculateBudgetsSummaries(
                        $bankAccount,
                        $monthStart,
                        $monthEnd,
                        $financialCategories,
                        FinancialCategoryTypeEnum::expenseTypes(),
                        null,
                        0
                    );
                    foreach ($budgetSummaries as $budgetSummary) {
                        /** @var BudgetSummary $budgetSummary */
                        if ($budgetSummary->summary > 0) {
                            $category = $budgetSummary->budget->getFinancialCategory();
                            $rootCategory = $category ? $budgetSummary->budget->getFinancialCategory()->getRootParent($rootFinancialCategory) : null;
                            $categoryLabel = $rootCategory ? $rootCategory->getLabel() : null;
                            $categoryLabel = $categoryLabel ? $categoryLabel : $category->getLabel();
                            if (!isset($dataByCategory[$categoryLabel])) {
                                $monthlyData[$categoryLabel] = 0;
                            }
                            $monthlyData[$categoryLabel] = bcsub($monthlyData[$categoryLabel], $budgetSummary->summary, 2);
                        }
                    }
                }
            }
            foreach ($monthlyData as $category => $amount) {
                $dataEntries[] = [
                    'category' => $category,
                    'amount' => $amount
                ];
            }

            $results[] = [
                'datas' => $dataEntries,
                'month' => $date->format("Y-m")
            ];
        }
        usort($results, function ($a, $b) {
            return $a['month'] <=> $b['month'];
        });
        return $results;
    }

    public function getAnnuaExpensesByCategory(ArrayCollection $bankAccounts, \DateTime $startDate, \DateTime $endDate, FinancialCategory $rootFinancialCategory = null): array
    {
        $results = [];

        $financialCategories = $this->financialCategoryService->getAllAccessibleFinancialCategoriesFlat($rootFinancialCategory);
        $transactions = $this->transactionRepository->getTransactions(
            $bankAccounts->toArray(),
            $startDate,
            $endDate,
            $financialCategories,
            FinancialCategoryTypeEnum::expenseTypes(),
            null,
            0
        );
        $scheduledTransactions = $this->scheduledTransactionRepository->findScheduledTransactions(
            $bankAccounts->toArray(),
            $startDate,
            $endDate,
            $financialCategories,
            FinancialCategoryTypeEnum::expenseTypes(),
            null,
            0
        );
        $predictedTransactions = $this->scheduledTransactionService->generatePredictedTransactions($scheduledTransactions, $startDate, $endDate);
        $transactions = array_merge($transactions, $predictedTransactions);

        $dataByCategory = [];
        foreach ($transactions as $transaction) {
            $category = $transaction->getFinancialCategory();
            $rootCategory = $category ? $transaction->getFinancialCategory()->getRootParent($rootFinancialCategory) : null;
            $categoryLabel = $rootCategory ? $rootCategory->getLabel() : ($category ? $category->getLabel() : null);
            if (!isset($dataByCategory[$categoryLabel])) {
                $dataByCategory[$categoryLabel] = 0;
            }
            $dataByCategory[$categoryLabel] = bcadd($dataByCategory[$categoryLabel], $transaction->getAmount(), 2);
        }

        $firstDayOfCurrentMonth = new DateTime('first day of this month');
        $firstDayOfCurrentMonth->setTime(0, 0, 0);
        if ($endDate > $firstDayOfCurrentMonth) {
            if ($startDate < $firstDayOfCurrentMonth) {
                $startDate = $firstDayOfCurrentMonth;
            }
            foreach ($bankAccounts as $bankAccount) {
                /** @var BudgetSummary[] $budgetSummaries */
                $budgetSummaries = $this->budgetService->calculateBudgetsSummaries(
                    $bankAccount,
                    $startDate,
                    $endDate,
                    $financialCategories,
                    FinancialCategoryTypeEnum::expenseTypes(),
                    null,
                    0
                );
                foreach ($budgetSummaries as $budgetSummary) {
                    /** @var BudgetSummary $budgetSummary */
                    if ($budgetSummary->summary > 0) {
                        $category = $budgetSummary->budget->getFinancialCategory();
                        $rootCategory = $category ? $budgetSummary->budget->getFinancialCategory()->getRootParent($rootFinancialCategory) : null;
                        $categoryLabel = $rootCategory ? $rootCategory->getLabel() : ($category ? $category->getLabel() : null);
                        if (!isset($dataByCategory[$categoryLabel])) {
                            $dataByCategory[$categoryLabel] = 0;
                        }
                        $dataByCategory[$categoryLabel] = bcsub($dataByCategory[$categoryLabel], $budgetSummary->summary, 2);
                    }
                }
            }
        }
        foreach ($dataByCategory as $category => $amount) {
            $results[] = [
                'category' => $category,
                'amount' => $amount
            ];
        }

        return $results;
    }

    public function getAnnualBalanceEvolutionByMonth(ArrayCollection $bankAccounts, \DateTime $startDate, \DateTime $endDate)
    {
        $results = [];
        $balance = 0;

        $firstDayOfCurrentMonth = new DateTime('first day of this month');
        $firstDayOfCurrentMonth->setTime(0, 0, 0);

        foreach ($bankAccounts as $bankAccount) {
            $balance += $this->bankAccountRepository->getBalanceAtDate($bankAccount, $startDate) ?? 0;
        }

        $period = new \DatePeriod($startDate, new \DateInterval('P1M'), $endDate);
        $scheduledTransactions = $this->scheduledTransactionRepository->findScheduledTransactions(
            $bankAccounts->toArray(),
            $startDate,
            $endDate,
            null,
            null,
            count($bankAccounts) === 1 ? null : [FinancialCategoryTypeEnum::Internal],
            0
        );

        foreach ($period as $date) {
            $month = $date->format('Y-m');

            $monthStart = new \DateTime($date->format("Y-m-01"));
            $monthEnd = new \DateTime($date->format("Y-m-t"));

            $transactionValues = $this->transactionRepository->getValue(
                [$bankAccount],
                $monthStart,
                $monthEnd,
                null,
                null,
                count($bankAccounts) === 1 ? null : [FinancialCategoryTypeEnum::Internal],
                0
            ) ?? 0;

            $balance = bcadd($balance, $transactionValues, 2);

            $predictedTransactions = $this->scheduledTransactionService->generatePredictedTransactions($scheduledTransactions, $monthStart, $monthEnd);


            foreach ($predictedTransactions as $transaction) {
                $balance = bcadd($balance, $transaction->getAmount(), 2);
            }

            if ($monthEnd >= $firstDayOfCurrentMonth && $monthStart >= $firstDayOfCurrentMonth) {

                foreach ($bankAccounts as $bankAccount) {
                    /** @var BudgetSummary[] $budgetSummaries */
                    $budgetSummaries = $this->budgetService->calculateBudgetsSummaries(
                        $bankAccount,
                        $monthStart,
                        $monthEnd,
                        null,
                        null,
                        count($bankAccounts) === 1 ? null : [FinancialCategoryTypeEnum::Internal],
                    );
                    foreach ($budgetSummaries as $budgetSummary) {
                        /** @var BudgetSummary $budgetSummary */
                        if ($budgetSummary->summary > 0) {
                            $balance = bcsub($balance, $budgetSummary->summary, 2);
                        }
                    }
                }
            }
            $results[] = new AnnualValueForMonth($balance, $month);
        }
        return $results;
    }
}
