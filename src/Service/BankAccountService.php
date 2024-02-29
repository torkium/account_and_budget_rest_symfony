<?php

namespace App\Service;

use App\DTO\BankAccountSummary;
use App\DTO\BudgetSummary;
use App\Entity\BankAccount;
use App\Entity\Budget;
use App\Entity\Transaction;
use App\Enum\FrequencyEnum;
use App\Repository\BankAccountRepository;
use App\Repository\BudgetRepository;
use App\Repository\TransactionRepository;
use App\Repository\ScheduledTransactionRepository;
use DateTime;
use DateTimeInterface;

class BankAccountService
{
    private BankAccountRepository $bankAccountRepository;
    private TransactionRepository $transactionRepository;
    private BudgetRepository $budgetRepository;
    private ScheduledTransactionRepository $scheduledTransactionRepository;
    private ScheduledTransactionService $scheduledTransactionService;
    private BudgetService $budgetService;

    public function __construct(
        BankAccountRepository $bankAccountRepository,
        TransactionRepository $transactionRepository,
        BudgetRepository $budgetRepository,
        ScheduledTransactionRepository $scheduledTransactionRepository,
        ScheduledTransactionService $scheduledTransactionService,
        BudgetService $budgetService,
    ) {
        $this->bankAccountRepository = $bankAccountRepository;
        $this->transactionRepository = $transactionRepository;
        $this->budgetRepository = $budgetRepository;
        $this->scheduledTransactionRepository = $scheduledTransactionRepository;
        $this->scheduledTransactionService = $scheduledTransactionService;
        $this->budgetService = $budgetService;
    }

    public function calculateBankAccountSummary(BankAccount $bankAccount, DateTimeInterface $startDate, DateTimeInterface $endDate): BankAccountSummary
    {
        $summary = new BankAccountSummary();
        $summary->setStartBalance($this->bankAccountRepository->getBalanceAtDate($bankAccount, $startDate) ?? 0);
        $summary->setCredit($this->transactionRepository->getCreditBetweenDate($bankAccount, $startDate, $endDate) ?? 0);
        $summary->setDebit($this->transactionRepository->getDebitBetweenDate($bankAccount, $startDate, $endDate) ?? 0);
        
        $scheduledTransactions = $this->scheduledTransactionRepository->findScheduledTransactionsByDateRange($bankAccount, $startDate, $endDate);
        $predictedTransactions = $this->scheduledTransactionService->generatePredictedTransactions($scheduledTransactions, $startDate, $endDate);
        $budgets = $this->budgetRepository->findBudgetsByDateRange($bankAccount, $startDate, $endDate);

        $summary->setProvisionalCredit($summary->getCredit());
        $summary->setProvisionalDebit($summary->getDebit());

        /** @var Transaction $transaction */
        foreach($predictedTransactions as $transaction){
            if($transaction->getAmount() >= 0){
                $summary->setProvisionalCredit($summary->getProvisionalCredit() + $transaction->getAmount());
            }
            else{
                $summary->setProvisionalDebit($summary->getProvisionalDebit() + $transaction->getAmount());
            }
        }

        /** @var BudgetSummary[] $budgetSummaries */
        $budgetSummaries = $this->budgetService->calculateBudgetSummary($bankAccount, $startDate, $endDate);
        /** @var Budget $budget */
        foreach($budgets as $budget){
            $budgetAmount = $this->calculateAdjustedAmountForPeriod($budget, $startDate, $endDate);
            $budgetSummary = array_filter($budgetSummaries, function($e) use ($budget){
                /** @var BudgetSummary $e */
                return $e->budget === $budget; // Condition pour filtrer
            })[0] ?? null;
            if($budgetSummary){
                $budgetAmount -= $budgetSummary->consumed;
            }
            if($budgetAmount < 0){
                $summary->setProvisionalCredit(bcadd($summary->getProvisionalCredit(), $budgetAmount, 2));
            }
            else{
                $summary->setProvisionalDebit(bcsub($summary->getProvisionalDebit(), $budgetAmount, 2));
            }
        }

        return $summary;
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
