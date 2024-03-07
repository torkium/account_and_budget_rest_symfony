<?php

namespace App\Service;

use App\DTO\Stats\AnnualValueForMonth;
use App\Enum\FinancialCategoryTypeEnum;
use App\Repository\TransactionRepository;
use App\Repository\ScheduledTransactionRepository;
use Doctrine\Common\Collections\ArrayCollection;

class StatsService
{
    private TransactionRepository $transactionRepository;
    private ScheduledTransactionRepository $scheduledTransactionRepository;
    private ScheduledTransactionService $scheduledTransactionService;

    public function __construct(
        TransactionRepository $transactionRepository,
        ScheduledTransactionRepository $scheduledTransactionRepository,
        ScheduledTransactionService $scheduledTransactionService,
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->scheduledTransactionRepository = $scheduledTransactionRepository;
        $this->scheduledTransactionService = $scheduledTransactionService;
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
        return $this->getValuesByMonth($bankAccounts,$startDate, $endDate, $transactions, $scheduledTransactions);
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
        return $this->getValuesByMonth($bankAccounts,$startDate, $endDate, $transactions, $scheduledTransactions);
    }

    private function getValuesByMonth(ArrayCollection $bankAccounts, \DateTime $startDate, \DateTime $endDate, array $transactions = [], array $scheduledTransactions = []){
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
}
