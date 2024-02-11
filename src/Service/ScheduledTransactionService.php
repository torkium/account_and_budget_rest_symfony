<?php

namespace App\Service;

use App\Entity\ScheduledTransaction;
use App\Entity\Transaction;
use App\Enum\FrequencyEnum;

class ScheduledTransactionService
{
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
        $currentDate = clone $scheduledTransaction->getStartDate();

        // Handle the ONCE frequency separately to check if the transaction date is within the period
        if ($scheduledTransaction->getFrequency() === FrequencyEnum::ONCE) {
            if ($currentDate >= $startDate && $currentDate <= $endDate) {
                $transactions[] = $this->createTransactionFromScheduled($scheduledTransaction, $currentDate);
            }
            return $transactions; // Return immediately as there's only one possible occurrence
        }

        // For other frequencies, generate transactions for each occurrence within the period
        while ($currentDate <= $endDate && ($scheduledTransaction->getEndDate() === null || $currentDate <= $scheduledTransaction->getEndDate())) {
            if ($currentDate >= $startDate) {
                $transactions[] = $this->createTransactionFromScheduled($scheduledTransaction, $currentDate);
            }

            // Increment the date according to the frequency
            $this->incrementDateByFrequency($currentDate, $scheduledTransaction->getFrequency());
        }

        return $transactions;
    }

    /**
     * Creates a Transaction instance from a ScheduledTransaction.
     *
     * @param ScheduledTransaction $scheduledTransaction The source ScheduledTransaction.
     * @param \DateTimeInterface $date The date for the transaction.
     * @return Transaction The created transaction.
     */
    private function createTransactionFromScheduled(ScheduledTransaction $scheduledTransaction, \DateTime $date): Transaction
    {
        $transaction = new Transaction();
        $transaction->setLabel($scheduledTransaction->getLabel());
        $transaction->setAmount($scheduledTransaction->getAmount());
        $transaction->setDate(clone $date); // Clone to avoid reference modification
        $transaction->setBankAccount($scheduledTransaction->getBankAccount());
        $transaction->setFinancialCategory($scheduledTransaction->getFinancialCategory());
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
}
