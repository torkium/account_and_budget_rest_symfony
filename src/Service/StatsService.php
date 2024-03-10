<?php

namespace App\Service;

use App\DTO\BudgetSummary;
use App\DTO\Stats\AnnualValueForMonth;
use App\Entity\FinancialCategory;
use App\Enum\FinancialCategoryTypeEnum;
use App\Repository\TransactionRepository;
use App\Repository\ScheduledTransactionRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;

class StatsService
{
    private TransactionRepository $transactionRepository;
    private ScheduledTransactionRepository $scheduledTransactionRepository;
    private ScheduledTransactionService $scheduledTransactionService;
    private FinancialCategoryService $financialCategoryService;
    private BudgetService $budgetService;

    public function __construct(
        TransactionRepository $transactionRepository,
        ScheduledTransactionRepository $scheduledTransactionRepository,
        ScheduledTransactionService $scheduledTransactionService,
        FinancialCategoryService $financialCategoryService,
        BudgetService $budgetService,
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->scheduledTransactionRepository = $scheduledTransactionRepository;
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

        $scheduledTransactions = $this->scheduledTransactionRepository->findCreditScheduledTransactionsByDateRange($bankAccounts, $startDate, $endDate);
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

        $scheduledTransactions = $this->scheduledTransactionRepository->findDebitScheduledTransactionsByDateRange($bankAccounts, $startDate, $endDate);
        return $this->getValuesByMonth($startDate, $endDate, $transactions, $scheduledTransactions);
    }

    private function getValuesByMonth(\DateTime $startDate, \DateTime $endDate, array $transactions = [], array $scheduledTransactions = [])
    {
        $annualValueByMonth = [];

        $predictedTransactions = $this->scheduledTransactionService->generatePredictedTransactions($scheduledTransactions, $startDate, $endDate);

        $transactions = array_merge($transactions, $predictedTransactions);

        $monthlyIncome = [];

        foreach ($transactions as $transaction) {
            $month = $transaction->getDate()->format('Y-m');

            if (!isset($monthlyIncome[$month])) {
                $monthlyIncome[$month] = 0;
            }

            $monthlyIncome[$month] += $transaction->getAmount();
        }

        foreach ($monthlyIncome as $month => $amount) {
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
                $monthlyData[$categoryLabel] += $transaction->getAmount();
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
        foreach ($period as $date) {
            $monthStart = new \DateTime($date->format("Y-m-01"));
            $monthEnd = new \DateTime($date->format("Y-m-t"));

            $transactions = $this->transactionRepository->getDebitTransactionsBetweenDates(
                $bankAccounts,
                $monthStart,
                $monthEnd,
                new ArrayCollection($financialCategories),
                null,
                count($bankAccounts) === 1 ? null : new ArrayCollection([FinancialCategoryTypeEnum::Internal])
            );
            $scheduledTransactions = $this->scheduledTransactionRepository->findDebitScheduledTransactionsByDateRange($bankAccounts, $startDate, $endDate, $financialCategories);
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

    public function getAnnuaExpensesByCategory(ArrayCollection $bankAccounts, \DateTime $startDate, \DateTime $endDate, FinancialCategory $rootFinancialCategory = null): array
    {
        $results = [];

        $financialCategories = $this->financialCategoryService->getAllAccessibleFinancialCategoriesFlat($rootFinancialCategory);
        $transactions = $this->transactionRepository->getDebitTransactionsBetweenDates(
            $bankAccounts,
            $startDate,
            $endDate,
            new ArrayCollection($financialCategories),
            null,
            count($bankAccounts) === 1 ? null : new ArrayCollection([FinancialCategoryTypeEnum::Internal])
        );
        $scheduledTransactions = $this->scheduledTransactionRepository->findDebitScheduledTransactionsByDateRange($bankAccounts, $startDate, $endDate, $financialCategories);
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

        $dayBeforeStartDate = (clone $startDate)->modify('-1 day');
        $balance = 0;
        foreach ($bankAccounts as $bankAccount) {
            $balance += $bankAccount->getInitialAmount();
            $creditBeforeStartDate = $this->transactionRepository->getCreditBetweenDate($bankAccount, null, $dayBeforeStartDate);
            $debitBeforeStartDate = $this->transactionRepository->getDebitBetweenDate($bankAccount, null, $dayBeforeStartDate);
            $balance += $creditBeforeStartDate + $debitBeforeStartDate;
        }
        $transactions = $this->transactionRepository->getCreditTransactionsBetweenDates(
            $bankAccounts,
            $startDate,
            $endDate,
            null,
            null,
            count($bankAccounts) === 1 ? null : new ArrayCollection([FinancialCategoryTypeEnum::Internal])
        );
        $scheduledTransactions = $this->scheduledTransactionRepository->findCreditScheduledTransactionsByDateRange($bankAccounts, $startDate, $endDate);
        $creditByMonth = $this->getValuesByMonth($startDate, $endDate, $transactions, $scheduledTransactions);

        $transactions = $this->transactionRepository->getDebitTransactionsBetweenDates(
            $bankAccounts,
            $startDate,
            $endDate,
            null,
            null,
            count($bankAccounts) === 1 ? null : new ArrayCollection([FinancialCategoryTypeEnum::Internal])
        );
        $this->scheduledTransactionRepository->findDebitScheduledTransactionsByDateRange($bankAccounts, $startDate, $endDate);
        $debitByMonth = $this->getValuesByMonth($startDate, $endDate, $transactions, $scheduledTransactions);

        $creditsMapped = array_reduce($creditByMonth, function ($carry, $item) {
            $carry[$item->month] = $item->amount;
            return $carry;
        }, []);

        $debitsMapped = array_reduce($debitByMonth, function ($carry, $item) {
            $carry[$item->month] = $item->amount;
            return $carry;
        }, []);

        $period = new \DatePeriod($startDate, new \DateInterval('P1M'), $endDate);
        $currentPeriod = date('Y-m');
        foreach ($period as $date) {
            $month = $date->format('Y-m');
            $monthStart = new \DateTime($date->format("Y-m-01"));
            $monthEnd = new \DateTime($date->format("Y-m-t"));
            if (isset($creditsMapped[$month])) {
                $balance += $creditsMapped[$month];
            }
            if (isset($debitsMapped[$month])) {
                $balance += $debitsMapped[$month];
            }
            //if ($date->format('Y-m') >= $currentPeriod) {
            //    foreach ($bankAccounts as $bankAccount) {
            //        $budgetsSummarries = $this->budgetService->calculateBudgetSummary($bankAccount, $monthStart, $monthEnd);
            //        foreach ($budgetsSummarries as $budgetsSummarry) {
            //            /** @var BudgetSummary $budgetsSummarry */
             //           $balance -= ($budgetsSummarry->consumed < $budgetsSummarry->budget->getAmount() ? $budgetsSummarry->budget->getAmount() - $budgetsSummarry->consumed : 0);
            //        }
            //    }
            //}
            $results[] = new AnnualValueForMonth($balance, $month);
        }
        return $results;
    }
}
