<?php

namespace App\Service;

use App\Entity\ScheduledTransaction;
use App\Entity\Transaction;
use App\Enum\FrequencyEnum;
use App\Repository\TransactionRepository;

class ScheduledTransactionService
{
    private TransactionRepository $transactionRepository;

    public function __construct(TransactionRepository $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    public function generatePredictedTransactions(array $scheduledTransactions, \DateTime $startDate, \DateTime $endDate)
    {
        return array_reduce($scheduledTransactions, function ($acc, ScheduledTransaction $scheduledTransaction) use ($startDate, $endDate) {
            return array_merge($acc, $this->generateTransactionsForPeriod($scheduledTransaction, $startDate, $endDate));
        }, []);
    }

    /**
     * Generates Transaction instances from a ScheduledTransaction for a given period.
     *
     * @param ScheduledTransaction $scheduledTransaction The ScheduledTransaction entity.
     * @param \DateTimeInterface $startDate Start date of the period.
     * @param \DateTimeInterface $endDate End date of the period.
     * @return Transaction[] The generated transactions.
     */
    public function generateTransactionsForPeriod(ScheduledTransaction $scheduledTransaction, \DateTime $startDate, \DateTime $endDate): array
    {
        $transactions = [];
        $realTransactions = $this->transactionRepository->findTransactionsByDateRange($scheduledTransaction->getBankAccount(), $startDate, $endDate, null, $scheduledTransaction);

        // Créez un tableau de dates pour lesquelles les transactions réelles existent
        $realTransactionDates = array_map(function ($transaction) {
            return $transaction->getDate()->format('Y-m-d');
        }, $realTransactions);

        $currentDate = clone $scheduledTransaction->getStartDate();

        // Handle the ONCE frequency separately to check if the transaction date is within the period
        if ($scheduledTransaction->getFrequency() === FrequencyEnum::ONCE) {
            if (!$realTransactionDates && $currentDate >= $startDate && $currentDate <= $endDate) {
                $transactions[] = $this->createTransactionFromScheduled($realTransactionDates, $scheduledTransaction, $currentDate);
            }
            return $transactions; // Return immediately as there's only one possible occurrence
        }

        // For other frequencies, generate transactions for each occurrence within the period
        while ($currentDate <= $endDate && ($scheduledTransaction->getEndDate() === null || $currentDate <= $scheduledTransaction->getEndDate())) {
            if ($currentDate >= $startDate) {
                $transaction = $this->createTransactionFromScheduled($realTransactionDates, $scheduledTransaction, $currentDate);
                if($transaction){
                    $transactions[] = $this->createTransactionFromScheduled($realTransactionDates, $scheduledTransaction, $currentDate);
                }
            }

            // Increment the date according to the frequency
            $this->incrementDateByFrequency($currentDate, $scheduledTransaction->getFrequency());
        }
        return $transactions;
    }

    /**
     * Creates a Transaction instance from a ScheduledTransaction.
     *
     * @param array $realTransactionDates Array of date of real transactions.
     * @param ScheduledTransaction $scheduledTransaction The source ScheduledTransaction.
     * @param \DateTimeInterface $date The date for the transaction.
     * @return ?Transaction|null The created transaction.
     */
    private function createTransactionFromScheduled(array $realTransactionDates, ScheduledTransaction $scheduledTransaction, \DateTime $date): ?Transaction
    {
        $existsRealTransaction = $this->checkForRealTransaction($realTransactionDates, $date, $scheduledTransaction->getFrequency());
        if($existsRealTransaction){
            return null;
        }
        $transaction = new Transaction();
        $transaction->setLabel($scheduledTransaction->getLabel());
        $transaction->setAmount($scheduledTransaction->getAmount());
        $transaction->setDate(clone $date); // Clone to avoid reference modification
        $transaction->setBankAccount($scheduledTransaction->getBankAccount());
        $transaction->setFinancialCategory($scheduledTransaction->getFinancialCategory());
        $transaction->setScheduledTransaction($scheduledTransaction);
        // Note: No ID as these transactions are not persisted yet
        return $transaction;
    }

    /**
     * Increments the date by the frequency specified in ScheduledTransaction.
     *
     * @param \DateTime &$date The date to increment.
     * @param FrequencyEnum $frequency The frequency of the scheduled transaction.
     */
    private function incrementDateByFrequency(\DateTime &$date, FrequencyEnum $frequency): void
    {
        switch ($frequency) {
            case FrequencyEnum::DAILY:
                $date->modify('+1 day');
                break;
            case FrequencyEnum::WEEKLY:
                $date->modify('+1 week');
                break;
            case FrequencyEnum::MONTHLY:
                $date->modify('+1 month');
                break;
            case FrequencyEnum::YEARLY:
                $date->modify('+1 year');
                break;
            default:
                throw new \InvalidArgumentException('Unsupported frequency: ' . $frequency);
        }
    }

    /**
     * Check for existing real transactions within the period defined by frequency.
     *
     * @param array $realTransactionDates Array of date of real transactions.
     * @param \DateTime $currentDate The date of the current iteration for scheduled transaction.
     * @param FrequencyEnum $frequency The frequency of the scheduled transaction.
     * @return bool True if a real transaction exists for the period, false otherwise.
     */
    private function checkForRealTransaction(array $realTransactionDates, \DateTime $currentDate, FrequencyEnum $frequency): bool
    {
        if (empty($realTransactionDates)) {
            return false;
        }
        if ($frequency === FrequencyEnum::ONCE) {
            return true;
        }
        foreach ($realTransactionDates as $realTransactionDate) {
            // Convert transaction date to DateTime object if not already
            $transactionDate = new \DateTime($realTransactionDate);

            switch ($frequency) {
                case FrequencyEnum::DAILY:
                    if ($transactionDate->format('Y-m-d') == $currentDate->format('Y-m-d')) {
                        return true;
                    }
                    break;
                case FrequencyEnum::WEEKLY:
                    if ($transactionDate->format("W") == $currentDate->format("W") && $transactionDate->format("Y") == $currentDate->format("Y")) {
                        return true;
                    }
                    break;
                case FrequencyEnum::MONTHLY:
                    if ($transactionDate->format("m") == $currentDate->format("m") && $transactionDate->format("Y") == $currentDate->format("Y")) {
                        return true;
                    }
                    break;
                case FrequencyEnum::YEARLY:
                    if ($transactionDate->format("Y") == $currentDate->format("Y")) {
                        return true;
                    }
                    break;
            }
        }
        return false;
    }
}
