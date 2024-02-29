<?php

namespace App\Service;

use App\DTO\BankAccountSummary;
use App\DTO\BudgetSummary;
use App\Entity\BankAccount;
use App\Entity\Budget;
use App\Entity\Transaction;
use App\Enum\FrequencyEnum;
use App\Repository\BankAccountRepository;
use App\Repository\TransactionRepository;
use App\Repository\ScheduledTransactionRepository;
use DateTime;
use DateTimeInterface;

class BankAccountService
{
    private BankAccountRepository $bankAccountRepository;
    private TransactionRepository $transactionRepository;
    private ScheduledTransactionRepository $scheduledTransactionRepository;
    private ScheduledTransactionService $scheduledTransactionService;

    public function __construct(
        BankAccountRepository $bankAccountRepository,
        TransactionRepository $transactionRepository,
        ScheduledTransactionRepository $scheduledTransactionRepository,
        ScheduledTransactionService $scheduledTransactionService
    ) {
        $this->bankAccountRepository = $bankAccountRepository;
        $this->transactionRepository = $transactionRepository;
        $this->scheduledTransactionRepository = $scheduledTransactionRepository;
        $this->scheduledTransactionService = $scheduledTransactionService;
    }

    public function calculateBankAccountSummary(BankAccount $bankAccount, DateTimeInterface $startDate, DateTimeInterface $endDate): BankAccountSummary
    {
        $summary = new BankAccountSummary();
        $summary->setStartBalance($this->bankAccountRepository->getBalanceAtDate($bankAccount, $startDate) ?? 0);
        $summary->setCredit($this->transactionRepository->getCreditBetweenDate($bankAccount, $startDate, $endDate) ?? 0);
        $summary->setDebit($this->transactionRepository->getDebitBetweenDate($bankAccount, $startDate, $endDate) ?? 0);
        
        $scheduledTransactions = $this->scheduledTransactionRepository->findScheduledTransactionsByDateRange($bankAccount, $startDate, $endDate);
        $predictedTransactions = $this->scheduledTransactionService->generatePredictedTransactions($scheduledTransactions, $startDate, $endDate);

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
