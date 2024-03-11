<?php

namespace App\Service;

use App\DTO\BudgetSummary;
use App\Entity\BankAccount;
use App\Entity\Budget;
use App\Enum\FrequencyEnum;
use App\Repository\BudgetRepository;
use App\Repository\TransactionRepository;
use App\Repository\ScheduledTransactionRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;

class BudgetService
{
    private BudgetRepository $budgetRepository;
    private TransactionRepository $transactionRepository;
    private ScheduledTransactionRepository $scheduledTransactionRepository;
    private ScheduledTransactionService $scheduledTransactionService;
    private FinancialCategoryService $financialCategoryService;

    public function __construct(
        BudgetRepository $budgetRepository,
        TransactionRepository $transactionRepository,
        ScheduledTransactionRepository $scheduledTransactionRepository,
        ScheduledTransactionService $scheduledTransactionService,
        FinancialCategoryService $financialCategoryService
    ) {
        $this->budgetRepository = $budgetRepository;
        $this->transactionRepository = $transactionRepository;
        $this->scheduledTransactionRepository = $scheduledTransactionRepository;
        $this->scheduledTransactionService = $scheduledTransactionService;
        $this->financialCategoryService = $financialCategoryService;
    }

    public function calculateBudgetsSummaries(BankAccount $bankAccount, DateTimeInterface $startDate, DateTimeInterface $endDate, array | null $financialCategories = null, array | null $financialCategoriesType = null, array | null $financialCategoriesTypeToExclude = null, $amountSign = null): array
    {
        $budgets = $this->budgetRepository->findBudgetsByDateRange([$bankAccount], $startDate, $endDate, $financialCategories, $financialCategoriesType, $financialCategoriesTypeToExclude, $amountSign);
        /** @var BudgetSummary[] $budgetSummaries */
        $budgetSummaries = [];

        foreach ($budgets as $budget) {
            $budgetSummaries[] = $this->calculateBudgetSummary($budget, $startDate, $endDate);
        }

        return $budgetSummaries;
    }

    public function calculateBudgetSummary(Budget $budget, DateTimeInterface $startDate, DateTimeInterface $endDate): BudgetSummary
    {
        $budget->setAmount($this->calculateAdjustedAmountForPeriod([$budget], $startDate, $endDate));
        $summary = new BudgetSummary($budget);
        $financialCategories = $this->financialCategoryService->getAllAccessibleFinancialCategoriesFlat($budget->getFinancialCategory());

        $realTransactions = $this->transactionRepository->findTransactionsByDateRange($budget->getBankAccount(), $startDate, $endDate, $financialCategories);
        $scheduledTransactions = $this->scheduledTransactionRepository->findScheduledTransactionsByDateRange(new ArrayCollection([$budget->getBankAccount()]), $startDate, $endDate, $financialCategories);
        $predictedTransactions = $this->scheduledTransactionService->generatePredictedTransactions($scheduledTransactions, $startDate, $endDate);
        $allTransactions = array_merge($realTransactions, $predictedTransactions);
        foreach ($allTransactions as $transaction) {
            if ($transaction->getId()) {
                $summary->consumed = bcadd($transaction->getAmount(), $summary->consumed, 2);
            }
            $summary->provisionalConsumed = bcadd($transaction->getAmount(), $summary->provisionalConsumed, 2);
        }
        $summary->summary = bcadd($budget->getAmount(), $summary->consumed, 2);
        $summary->provisionalSummary = bcadd($budget->getAmount(), $summary->provisionalConsumed, 2);

        return $summary;
    }

    /**
     * 
     *
     * @param Budget[] $budgets 
     * @param DateTime $startDate 
     * @param DateTime $endDate 
     * @return float 
     */
    public function calculateAdjustedAmountForPeriod(array $budgets, DateTime $startDate, DateTime $endDate): float
    {
        $adjustedAmount = 0;
        foreach ($budgets as $budget) {
            $frequency = $budget->getFrequency();
            $amount = $budget->getAmount();

            $periodCount = $this->calculatePeriodCount($startDate, $endDate, $frequency);

            $adjustedAmount = $amount * $periodCount;
        }

        return $adjustedAmount;
    }

    /**
     * 
     *
     * @param BankAccount $bankAccount 
     * @param DateTime $startDate 
     * @param DateTime $endDate 
     * @return float 
     */
    public function calculateAdjustedAmountForPeriodForBankAccount(BankAccount $bankAccount, DateTime $startDate, DateTime $endDate): float
    {
        $budgets = $this->budgetRepository->findBy(["bankAccount" => $bankAccount]);
        return $this->calculateAdjustedAmountForPeriod($budgets, $startDate, $endDate);
    }

    /**
     * 
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param string $frequency
     * @return int 
     */
    private function calculatePeriodCount(DateTime $startDate, DateTime $endDate, $frequency): int
    {
        switch ($frequency) {
            case FrequencyEnum::MONTHLY:
                $startDay = (int) $startDate->format('d');
                $endDay = (int) $endDate->format('d');
                $endMonthDays = (int) $endDate->format('t');
                $months = $endDate->diff($startDate)->m + ($endDate->diff($startDate)->y * 12);
                if ($startDay === 1 && $endDay === $endMonthDays) {
                    $months += 1;
                }

                return $months;

            case FrequencyEnum::WEEKLY:
                $startTimestamp = $startDate->getTimestamp();
                $endTimestamp = $endDate->getTimestamp();
                $diffInSeconds = $endTimestamp - $startTimestamp;
                $weeks = intdiv($diffInSeconds, (7 * 24 * 60 * 60));
                return $weeks;
        }

        return 0;
    }
}
