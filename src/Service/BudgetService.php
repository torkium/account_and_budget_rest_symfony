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

    public function calculateBudgetSummary(BankAccount $bankAccount, DateTimeInterface $startDate, DateTimeInterface $endDate): array
    {
        $budgets = $this->budgetRepository->findBudgetsByDateRange($bankAccount, $startDate, $endDate);
        /** @var BudgetSummary[] $budgetSummaries */
        $budgetSummaries = [];

        foreach ($budgets as $budget) {
            $budget->setAmount($this->calculateAdjustedAmountForPeriod($budget, $startDate, $endDate));
            $summary = new BudgetSummary($budget);
            $financialCategories = $this->financialCategoryService->getAllAccessibleFinancialCategoriesFlat($budget->getFinancialCategory());

            $realTransactions = $this->transactionRepository->findTransactionsByDateRange($bankAccount, $startDate, $endDate, $financialCategories);
            $scheduledTransactions = $this->scheduledTransactionRepository->findScheduledTransactionsByDateRange($bankAccount, $startDate, $endDate, $financialCategories);
            $predictedTransactions = $this->scheduledTransactionService->generatePredictedTransactions($scheduledTransactions, $startDate, $endDate);
            $allTransactions = array_merge($realTransactions, $predictedTransactions);
            foreach ($allTransactions as $transaction) {
                if ($transaction->getId()) {
                    $summary->consumed = bcadd((string)$transaction->getAmount(), (string) $summary->consumed, 2);
                }
                $summary->provisionalConsumed = bcadd((string) $transaction->getAmount(), (string) $summary->provisionalConsumed, 2);
            }
            $summary->summary = bcadd((string) $budget->getAmount(), (string) $summary->consumed, 2);
            $summary->provisionalSummary = bcadd((string) $budget->getAmount(), (string) $summary->provisionalConsumed, 2);

            $budgetSummaries[] = $summary;
        }

        return $budgetSummaries;
    }

    /**
     * Calcule le montant ajusté d'un budget pour une période donnée.
     *
     * @param Budget $budget Le budget dont le montant doit être calculé.
     * @param DateTime $startDate La date de début de la période.
     * @param DateTime $endDate La date de fin de la période.
     * @return float Le montant ajusté du budget.
     */
    public function calculateAdjustedAmountForPeriod(Budget $budget, DateTime $startDate, DateTime $endDate): float
    {
        $frequency = $budget->getFrequency();
        $amount = $budget->getAmount();

        // Calcule le nombre de périodes complètes entre startDate et endDate selon la fréquence du budget
        $periodCount = $this->calculatePeriodCount($startDate, $endDate, $frequency);

        // Ajuste le montant du budget en fonction du nombre de périodes
        $adjustedAmount = $amount * $periodCount;

        return $adjustedAmount;
    }

    /**
     * Calcule le nombre de périodes complètes entre deux dates, en fonction de la fréquence.
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param string $frequency
     * @return int Le nombre de périodes complètes.
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
