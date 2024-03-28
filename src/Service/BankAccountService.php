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
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

class BankAccountService
{
    private BankAccountRepository $bankAccountRepository;
    private TransactionRepository $transactionRepository;
    private BudgetRepository $budgetRepository;
    private ScheduledTransactionRepository $scheduledTransactionRepository;
    private ScheduledTransactionService $scheduledTransactionService;
    private BudgetService $budgetService;
    private EntityManagerInterface $entityManager;

    public function __construct(
        BankAccountRepository $bankAccountRepository,
        TransactionRepository $transactionRepository,
        BudgetRepository $budgetRepository,
        ScheduledTransactionRepository $scheduledTransactionRepository,
        ScheduledTransactionService $scheduledTransactionService,
        BudgetService $budgetService,
        EntityManagerInterface $entityManager,
    ) {
        $this->bankAccountRepository = $bankAccountRepository;
        $this->transactionRepository = $transactionRepository;
        $this->budgetRepository = $budgetRepository;
        $this->scheduledTransactionRepository = $scheduledTransactionRepository;
        $this->scheduledTransactionService = $scheduledTransactionService;
        $this->budgetService = $budgetService;
        $this->entityManager = $entityManager;
    }

    public function calculateBankAccountSummary(BankAccount $bankAccount, DateTimeInterface $startDate, DateTimeInterface $endDate): BankAccountSummary
    {
        $summary = new BankAccountSummary();
        $summary->setStartBalance($this->bankAccountRepository->getBalanceAtDate($bankAccount, $startDate) ?? 0);
        $summary->setCredit($this->transactionRepository->getValue([$bankAccount], $startDate, $endDate, null, null, null, 1) ?? 0);
        $summary->setDebit($this->transactionRepository->getValue([$bankAccount], $startDate, $endDate, null, null, null, -1) ?? 0);
        $realExpenses = $this->transactionRepository->getValue([$bankAccount], $startDate, $endDate, null, FinancialCategoryTypeEnum::expenseTypes(), null, 0) ?? 0;
        $realExpenses = bcadd($realExpenses, $this->transactionRepository->getValue([$bankAccount], $startDate, $endDate, null, [FinancialCategoryTypeEnum::Undefined], null, -1) ?? 0, 2);
        $summary->setRealExpenses($realExpenses);
        $firstDayOfCurrentMonth = new DateTime('first day of this month');
        $firstDayOfCurrentMonth->setTime(0, 0, 0);
        $firstDayOfNextMonth = new DateTime('last day of this month');
        $firstDayOfNextMonth->modify("+1 day")->setTime(0, 0, 0);

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

        if ($endDate >= $firstDayOfCurrentMonth && $startDate >= $firstDayOfCurrentMonth) {
            $this->entityManager->clear();
            /** @var BudgetSummary[] $budgetSummaries */
            $budgetSummaries = $this->budgetService->calculateBudgetsSummaries($bankAccount, $startDate, $endDate);
            foreach ($budgetSummaries as $budgetSummary) {
                /** @var BudgetSummary $budgetSummary */
                if ($budgetSummary->summary > 0) {
                    $summary->setProvisionalDebit(bcsub($summary->getProvisionalDebit(), $budgetSummary->summary, 2));
                }
            }
        }

        $provisionalStartBalanceEndDate = (new DateTime())->setTimestamp($startDate->getTimestamp());
        $provisionalStartBalanceEndDate->modify('-1 day')->setTime(23, 59, 59);
        if($firstDayOfNextMonth < $provisionalStartBalanceEndDate){
            $provisionnalTransactionsBeforeDate = 0;
            $scheduledTransactions = $this->scheduledTransactionRepository->findScheduledTransactionsByDateRange(new ArrayCollection([$bankAccount]), $firstDayOfNextMonth, $provisionalStartBalanceEndDate);
            $predictedTransactions = $this->scheduledTransactionService->generatePredictedTransactionsUntilDate($scheduledTransactions, $provisionalStartBalanceEndDate);
            /** @var Transaction $transaction */
            foreach ($predictedTransactions as $transaction) {
                $provisionnalTransactionsBeforeDate = bcadd($provisionnalTransactionsBeforeDate, $transaction->getAmount(), 2);
            }
        }
        if($startDate > $firstDayOfNextMonth){
            /** @var BudgetSummary[] $budgetSummaries */
            $this->entityManager->clear();
            $budgetSummaries = $this->budgetService->calculateBudgetsSummaries($bankAccount, $firstDayOfNextMonth, $provisionalStartBalanceEndDate);
            foreach ($budgetSummaries as $budgetSummary) {
                /** @var BudgetSummary $budgetSummary */
                if ($budgetSummary->summary > 0) {
                    $provisionnalTransactionsBeforeDate = bcsub($provisionnalTransactionsBeforeDate, $budgetSummary->summary, 2);
                }
            }
            $summary->setProvisionalStartBalance(bcadd($summary->getStartBalance(), $provisionnalTransactionsBeforeDate, 2));
        }
        else{
            $summary->setProvisionalStartBalance($summary->getStartBalance());
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

    private function getTheoricalBalanceAtDate(BankAccount $bankAccount, DateTimeInterface $date){
        //récupérer toutes les transactions réelles avant la date

        //récupérer toutes les transactions prévisionnelles avant la date

        //récupérer tous les budgets avant la date, à partir du mois suivant le mois courant,  si la date est supérieure au mois suivant

    }
}
