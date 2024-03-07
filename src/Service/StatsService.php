<?php

namespace App\Service;

use App\DTO\Stats\AnnualValueForMonth;
use App\Entity\FinancialCategory;
use App\Enum\FinancialCategoryTypeEnum;
use App\Repository\FinancialCategoryRepository;
use App\Repository\TransactionRepository;
use App\Repository\ScheduledTransactionRepository;
use Doctrine\Common\Collections\ArrayCollection;

class StatsService
{
    private TransactionRepository $transactionRepository;
    private ScheduledTransactionRepository $scheduledTransactionRepository;
    private ScheduledTransactionService $scheduledTransactionService;
    private FinancialCategoryService $financialCategoryService;
    private FinancialCategoryRepository $financialCategoryRepository;

    public function __construct(
        TransactionRepository $transactionRepository,
        ScheduledTransactionRepository $scheduledTransactionRepository,
        ScheduledTransactionService $scheduledTransactionService,
        FinancialCategoryService $financialCategoryService,
        FinancialCategoryRepository $financialCategoryRepository
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->scheduledTransactionRepository = $scheduledTransactionRepository;
        $this->scheduledTransactionService = $scheduledTransactionService;
        $this->financialCategoryService = $financialCategoryService;
        $this->financialCategoryRepository = $financialCategoryRepository;
    }

    public function getAnnualIncomesByMonth(ArrayCollection $bankAccounts, \DateTime $startDate, \DateTime $endDate): array
    {
        $transactions = $this->transactionRepository->getCreditTransactionsBetweenDates(
            $bankAccounts,
            $startDate,
            $endDate,
            null,
            new ArrayCollection([FinancialCategoryTypeEnum::Internal])
        );

        $scheduledTransactions = $this->scheduledTransactionRepository->findCreditScheduledTransactionsByDateRange($bankAccounts, $startDate, $endDate);
        return $this->getValuesByMonth($bankAccounts, $startDate, $endDate, $transactions, $scheduledTransactions);
    }

    public function getAnnualExpensesByMonth(ArrayCollection $bankAccounts, \DateTime $startDate, \DateTime $endDate): array
    {
        $transactions = $this->transactionRepository->getDebitTransactionsBetweenDates(
            $bankAccounts,
            $startDate,
            $endDate,
            null,
            new ArrayCollection([FinancialCategoryTypeEnum::Internal])
        );

        $scheduledTransactions = $this->scheduledTransactionRepository->findDebitScheduledTransactionsByDateRange($bankAccounts, $startDate, $endDate);
        return $this->getValuesByMonth($bankAccounts, $startDate, $endDate, $transactions, $scheduledTransactions);
    }

    private function getValuesByMonth(ArrayCollection $bankAccounts, \DateTime $startDate, \DateTime $endDate, array $transactions = [], array $scheduledTransactions = [])
    {
        $annualIncomesByMonth = [];

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
            $annualIncomesByMonth[] = new AnnualValueForMonth($amount, $month);
        }

        return $annualIncomesByMonth;
    }

    public function getAnnualValuesByCategoryByMonth(ArrayCollection $bankAccounts, \DateTime $startDate, \DateTime $endDate, FinancialCategory $rootFinancialCategory = null): array
    {
        $results = [];

        $period = new \DatePeriod($startDate, new \DateInterval('P1M'), $endDate);
        foreach ($period as $date) {
            $monthStart = new \DateTime($date->format("Y-m-01"));
            $monthEnd = new \DateTime($date->format("Y-m-t"));

            $financialCategories = $this->financialCategoryService->getAllAccessibleFinancialCategoriesFlat($rootFinancialCategory);
            $transactions = $this->transactionRepository->findByDateRangeAndCategory(
                $bankAccounts,
                $monthStart,
                $monthEnd,
                new ArrayCollection($financialCategories),
                null,
                new ArrayCollection([FinancialCategoryTypeEnum::Internal])
            );
            $scheduledTransactions = $this->scheduledTransactionRepository->findScheduledTransactionsByDateRange($bankAccounts, $monthStart, $monthEnd, $financialCategories);
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
            new ArrayCollection([FinancialCategoryTypeEnum::Internal])
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
            $dataByCategory[$categoryLabel] += $transaction->getAmount();
        }
    
        foreach ($dataByCategory as $category => $amount) {
            $results[] = [
                'category' => $category,
                'amount' => $amount
            ];
        }
    
        return $results;
    }
}
