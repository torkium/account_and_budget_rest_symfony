<?php

namespace App\EventListener;

use Doctrine\ORM\Event\PreRemoveEventArgs;
use App\Entity\ScheduledTransaction;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;

class ScheduledTransactionListener
{
    private $transactionRepository;

    public function __construct(TransactionRepository $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }
    
    public function preRemove(PreRemoveEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof ScheduledTransaction) {
            return;
        }
        $this->transactionRepository->unsetScheduledTransactionForAll($entity);
    }
}
