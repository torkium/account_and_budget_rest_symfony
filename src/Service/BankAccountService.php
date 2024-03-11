<?php

namespace App\Service;

use App\DTO\BankAccountSummary;
use App\DTO\BudgetSummary;
use App\Entity\BankAccount;
use App\Entity\Budget;
use App\Entity\Transaction;
use App\Enum\FinancialCategoryTypeEnum;
use App\Enum\FrequencyEnum;
use App\Repository\BankAccountRepository;
use App\Repository\BudgetRepository;
use App\Repository\TransactionRepository;
use App\Repository\ScheduledTransactionRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;

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
        $summary->setCredit($this->transactionRepository->getValue([$bankAccount], $startDate, $endDate, null, null, null, 1) ?? 0);
        $summary->setDebit($this->transactionRepository->getValue([$bankAccount], $startDate, $endDate, null, null, null, -1) ?? 0);
        $realExpenses = $this->transactionRepository->getValue([$bankAccount], $startDate, $endDate, null, FinancialCategoryTypeEnum::expenseTypes(), null, 0) ?? 0;
        $realExpenses += $this->transactionRepository->getValue([$bankAccount], $startDate, $endDate, null, [FinancialCategoryTypeEnum::Undefined], null, -1) ?? 0;
        $summary->setRealExpenses($realExpenses);

        $scheduledTransactions = $this->scheduledTransactionRepository->findScheduledTransactionsByDateRange(new ArrayCollection([$bankAccount]), $startDate, $endDate);
        $predictedTransactions = $this->scheduledTransactionService->generatePredictedTransactions($scheduledTransactions, $startDate, $endDate);

        $summary->setProvisionalCredit($summary->getCredit());
        $summary->setProvisionalDebit($summary->getDebit());

        /** @var Transaction $transaction */
        foreach ($predictedTransactions as $transaction) {
            if ($transaction->getAmount() >= 0) {
                $summary->setProvisionalCredit(bcadd($summary->getProvisionalCredit(), $transaction->getAmount(), 2));
            } else {
                $summary->setProvisionalDebit(bcadd($summary->getProvisionalDebit(), $transaction->getAmount(), 2));
            }
        }

        $firstDayOfCurrentMonth = new DateTime('first day of this month');
        $firstDayOfCurrentMonth->setTime(0, 0, 0);
        if ($endDate >= $firstDayOfCurrentMonth && $startDate >= $firstDayOfCurrentMonth) {
            /** @var BudgetSummary[] $budgetSummaries */
            $budgetSummaries = $this->budgetService->calculateBudgetsSummaries($bankAccount, $startDate, $endDate);
            /** @var Budget $budget */
            foreach ($budgetSummaries as $budgetSummary) {
                /** @var BudgetSummary $budgetSummary */
                if ($budgetSummary->summary > 0) {
                    $summary->setProvisionalDebit(bcsub($summary->getProvisionalDebit(), $budgetSummary->summary, 2));
                }
            }
        }
        return $summary;
    }

    public function calculateBankAccountSummaryByMonth(BankAccount $bankAccount, DateTimeInterface $startDate, DateTimeInterface $endDate): array
    {
        $results = [];
        $period = new \DatePeriod($startDate, new \DateInterval('P1M'), $endDate);
        foreach ($period as $date) {
            $monthStart = new \DateTime($date->format("Y-m-01"));
            $monthEnd = new \DateTime($date->format("Y-m-t"));

            $results[] = [
                'datas' => $this->calculateBankAccountSummary($bankAccount, $monthStart, $monthEnd),
                'month' => $date->format("Y-m")
            ];
        }
        return $results;
    }

    public function calculateAdjustedAmountForPeriod(Budget $budget, DateTime $startDate, DateTime $endDate): float
    {
        $frequency = $budget->getFrequency();
        $amount = $budget->getAmount();

        $periodCount = $this->calculatePeriodCount($startDate, $endDate, $frequency);

        $adjustedAmount = $amount * $periodCount;

        return $adjustedAmount;
    }

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
