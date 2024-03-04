<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Transaction;
use App\Entity\BankAccount;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\FinancialCategoryRepository;
use App\Repository\ScheduledTransactionRepository;
use App\Service\ScheduledTransactionService;
use Symfony\Component\HttpFoundation\Response;

#[Route('/bank-accounts/{bankAccount}/transactions', name: 'app_api_transaction')]
class TransactionController extends AbstractController
{

    #[Route('/', name: 'app_api_transaction_index', methods: 'GET')]
    public function index(Request $request, BankAccount $bankAccount, TransactionRepository $transactionRepository, ScheduledTransactionRepository $scheduledTransactionRepository, ScheduledTransactionService $scheduledTransactionService)
    {
        $this->denyAccessUnlessGranted('VIEW', $bankAccount);

        $startDateInput = $request->query->get('start_date');
        $endDateInput = $request->query->get('end_date');
        $startDate = $startDateInput ? new \DateTime($startDateInput) : null;
        $endDate = $endDateInput ? new \DateTime($endDateInput) : null;
        if (!$startDate || !$endDate) {
            return $this->json(['error' => 'start_date and end_date required.'], Response::HTTP_BAD_REQUEST);
        }
        $interval = $startDate->diff($endDate);
        if ($interval->m > 3 || $interval->y > 0 || ($interval->m == 3 && $interval->d > 0)) {
            return $this->json(['error' => 'Period should not be greater than 3 months.'], Response::HTTP_BAD_REQUEST);
        }

        $realTransactions = $transactionRepository->findTransactionsByDateRange($bankAccount, $startDate, $endDate);

        $applicableScheduledTransactions = $scheduledTransactionRepository->findScheduledTransactionsByDateRange($bankAccount, $startDate, $endDate);
        $predictedTransactions = [];
        foreach ($applicableScheduledTransactions as $scheduledTransaction) {
            $predictedTransactions = array_merge($predictedTransactions, $scheduledTransactionService->generateTransactionsForPeriod($scheduledTransaction, $startDate, $endDate));
        }

        $allTransactions = array_merge($realTransactions, $predictedTransactions);
        $allTransactions = array_reduce($allTransactions, function($acc, $transaction){
            /** @var Transaction $transaction */
            if($transaction->getAmount() !== 0.0){
                $acc[] = $transaction;
            }
            return $acc;
        }, []);
        usort($allTransactions, function($a, $b) {
            return $a->getDate() <=> $b->getDate();
        });

        return $this->json($allTransactions, Response::HTTP_OK, [], ['groups' => ['transaction_get', 'financial_category_get', 'financial_category_get_parent', 'scheduled_transaction_get']]);
    }

    #[Route('/{transaction}', name: 'app_api_transaction_show', methods: 'GET')]
    public function show(BankAccount $bankAccount, Transaction $transaction)
    {
        if ($this->isTransactionOnBankAccount($bankAccount, $transaction)) {
            return $this->json(['error' => 'Transaction is not linked to this bank account.'], Response::HTTP_BAD_REQUEST);
        }
        $this->denyAccessUnlessGranted('VIEW', $bankAccount);
        $this->denyAccessUnlessGranted('VIEW', $transaction);
        return $this->json($transaction, 200, [], ['groups' => ['transaction_get', 'financial_category_get', 'financial_category_get_parent']]);
    }

    #[Route('/', name: 'app_api_transaction_create', methods: 'POST')]
    public function create(Request $request, BankAccount $bankAccount, EntityManagerInterface $entityManager, FinancialCategoryRepository $financialCategoryRepository, ScheduledTransactionRepository $scheduledTransactionRepository)
    {
        $this->denyAccessUnlessGranted('EDIT', $bankAccount);
        $data = json_decode($request->getContent(), true);

        $transaction = new Transaction();
        $transaction->setReference($data['reference'] ?? null);
        $transaction->setLabel($data['label']);
        $transaction->setAmount($data['amount']);
        $transaction->setDate(new \DateTime($data['date']));
        $transaction->setBankAccount($bankAccount);

        if (isset($data['financialCategory'])) {
            $financialCategory = $financialCategoryRepository->find($data['financialCategory']);
            $this->denyAccessUnlessGranted('VIEW', $financialCategory);
            $transaction->setFinancialCategory($financialCategory);
        }


        if (isset($data['scheduledTransactionId'])) {
            $scheduledTransaction = $scheduledTransactionRepository->find($data['scheduledTransactionId']);
            if ($scheduledTransaction) {
                $this->denyAccessUnlessGranted('VIEW', $scheduledTransaction);
                if ($scheduledTransaction->getBankAccount()->getId() !== $transaction->getBankAccount()->getId()) {
                    return $this->json(['error' => 'Transaction and SheduledTransaction are not linked to the same bank account.'], Response::HTTP_BAD_REQUEST);
                }
                if ($scheduledTransaction->getFinancialCategory()->getId() !== $transaction->getFinancialCategory()->getId()) {
                    return $this->json(['error' => 'Transaction and SheduledTransaction are not linked to the same financial category.'], Response::HTTP_BAD_REQUEST);
                }
                $transaction->setScheduledTransaction($scheduledTransaction);
            }
        }

        $entityManager->persist($transaction);
        $entityManager->flush();

        return $this->json($transaction, 201, [], ['groups' => ['transaction_get', 'financial_category_get', 'financial_category_get_parent', 'scheduled_transaction_get']]);
    }

    #[Route('/{transaction}', name: 'app_api_transaction_edit', methods: 'PUT')]
    public function edit(Request $request, BankAccount $bankAccount, Transaction $transaction, EntityManagerInterface $entityManager, FinancialCategoryRepository $financialCategoryRepository, ScheduledTransactionRepository $scheduledTransactionRepository)
    {
        if ($this->isTransactionOnBankAccount($bankAccount, $transaction)) {
            return $this->json(['error' => 'Transaction is not linked to this bank account.'], Response::HTTP_BAD_REQUEST);
        }
        $this->denyAccessUnlessGranted('VIEW', $bankAccount);
        $this->denyAccessUnlessGranted('EDIT', $transaction);
        $data = json_decode($request->getContent(), true);
        $transaction->setReference($data['reference'] ?? null);
        $transaction->setLabel($data['label']);
        $transaction->setAmount($data['amount']);
        $transaction->setDate(new \DateTime($data['date']));

        if (isset($data['financialCategory'])) {
            $financialCategory = $financialCategoryRepository->find($data['financialCategory']);
            $this->denyAccessUnlessGranted('VIEW', $financialCategory);
            $transaction->setFinancialCategory($financialCategory);
        }
        else{
            $transaction->setFinancialCategory(null);
        }

        if (isset($data['scheduledTransactionId'])) {
            $scheduledTransaction = $scheduledTransactionRepository->find($data['scheduledTransactionId']);
            if ($scheduledTransaction) {
                $this->denyAccessUnlessGranted('VIEW', $scheduledTransaction);
                if ($scheduledTransaction->getBankAccount()->getId() !== $transaction->getBankAccount()->getId()) {
                    return $this->json(['error' => 'Transaction and SheduledTransaction are not linked to the same bank account.'], Response::HTTP_BAD_REQUEST);
                }
                if ($scheduledTransaction->getFinancialCategory()->getId() !== $transaction->getFinancialCategory()->getId()) {
                    return $this->json(['error' => 'Transaction and SheduledTransaction are not linked to the same financial category.'], Response::HTTP_BAD_REQUEST);
                }
                $transaction->setScheduledTransaction($scheduledTransaction);
            } else {
                $transaction->setScheduledTransaction(null);
            }
        }

        $entityManager->flush();

        return $this->json($transaction, 200, [], ['groups' => ['transaction_get', 'financial_category_get', 'financial_category_get_parent', 'scheduled_transaction_get']]);
    }

    #[Route('/{transaction}', name: 'app_api_transaction_delete', methods: 'DELETE')]
    public function delete(BankAccount $bankAccount, Transaction $transaction, EntityManagerInterface $entityManager)
    {
        if ($this->isTransactionOnBankAccount($bankAccount, $transaction)) {
            return $this->json(['error' => 'Transaction is not linked to this bank account.'], Response::HTTP_BAD_REQUEST);
        }
        $this->denyAccessUnlessGranted('VIEW', $bankAccount);
        $this->denyAccessUnlessGranted('DELETE', $transaction);
        $entityManager->remove($transaction);
        $entityManager->flush();

        return new JsonResponse(null, 204);
    }

    protected function isTransactionOnBankAccount(BankAccount $bankAccount, Transaction $transaction)
    {
        return ($transaction->getBankAccount()->getId() !== $bankAccount->getId());
    }
}
