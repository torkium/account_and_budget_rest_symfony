<?php

namespace App\Controller;

use App\Entity\BankAccount;
use App\Entity\ScheduledTransaction;
use App\Entity\Transaction;
use App\Repository\ScheduledTransactionRepository;
use App\Repository\FinancialCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Enum\FrequencyEnum;

#[Route('/bank-accounts/{bankAccount}/scheduled-transactions', name: 'app_api_scheduled_transaction')]
class ScheduledTransactionController extends AbstractController
{
    #[Route('/', name: 'app_api_scheduled_transaction_index', methods: 'GET')]
    public function index(Request $request, BankAccount $bankAccount, ScheduledTransactionRepository $scheduledTransactionRepository)
    {
        $this->denyAccessUnlessGranted('VIEW', $bankAccount);
        
        $scheduledTransactions = $scheduledTransactionRepository->findBy(['bankAccount' => $bankAccount->getId()]);

        return $this->json($scheduledTransactions, Response::HTTP_OK, [], ['groups' => ['scheduled_transaction_get', 'financial_category_get', 'financial_category_get_parent']]);
    }

    #[Route('/{scheduledTransaction}', name: 'app_api_scheduled_transaction_show', methods: 'GET')]
    public function show(BankAccount $bankAccount, ScheduledTransaction $scheduledTransaction)
    {
        if ($this->isScheduledTransactionOnBankAccount($bankAccount, $scheduledTransaction)) {
            return $this->json(['error' => 'Scheduled Transaction is not linked to this bank account.'], Response::HTTP_BAD_REQUEST);
        }
        $this->denyAccessUnlessGranted('VIEW', $bankAccount);
        $this->denyAccessUnlessGranted('VIEW', $scheduledTransaction);
        return $this->json($scheduledTransaction, Response::HTTP_OK, [], ['groups' => ['scheduled_transaction_get', 'financial_category_get', 'financial_category_get_parent']]);
    }

    #[Route('/', name: 'app_api_scheduled_transaction_create', methods: 'POST')]
    public function create(Request $request, BankAccount $bankAccount, EntityManagerInterface $entityManager, FinancialCategoryRepository $financialCategoryRepository)
    {
        $this->denyAccessUnlessGranted('EDIT', $bankAccount);
        $data = json_decode($request->getContent(), true);

        $scheduledTransaction = new ScheduledTransaction();
        $scheduledTransaction->setLabel($data['label']);
        $scheduledTransaction->setAmount($data['amount']);
        $scheduledTransaction->setStartDate(new \DateTime($data['startDate']));
        $scheduledTransaction->setEndDate(isset($data['endDate']) && !empty($data['endDate']) ? new \DateTime($data['endDate']) : null);
        $scheduledTransaction->setFrequency(FrequencyEnum::from($data['frequency']));
        $scheduledTransaction->setBankAccount($bankAccount);

        if (isset($data['financialCategory'])) {
            $financialCategory = $financialCategoryRepository->find($data['financialCategory']);
            $this->denyAccessUnlessGranted('VIEW', $financialCategory);
            $scheduledTransaction->setFinancialCategory($financialCategory);
        }

        $entityManager->persist($scheduledTransaction);
        $entityManager->flush();

        return $this->json($scheduledTransaction, Response::HTTP_CREATED, [], ['groups' => ['scheduled_transaction_get', 'financial_category_get', 'financial_category_get_parent']]);
    }

    #[Route('/{scheduledTransaction}', name: 'app_api_scheduled_transaction_edit', methods: 'PUT')]
    public function edit(Request $request, BankAccount $bankAccount, ScheduledTransaction $scheduledTransaction, EntityManagerInterface $entityManager, FinancialCategoryRepository $financialCategoryRepository)
    {
        if ($this->isScheduledTransactionOnBankAccount($bankAccount, $scheduledTransaction)) {
            return $this->json(['error' => 'Scheduled Transaction is not linked to this bank account.'], Response::HTTP_BAD_REQUEST);
        }
        $this->denyAccessUnlessGranted('VIEW', $bankAccount);
        $this->denyAccessUnlessGranted('EDIT', $scheduledTransaction);
        $data = json_decode($request->getContent(), true);

        $scheduledTransaction->setLabel($data['label']);
        $scheduledTransaction->setAmount($data['amount']);
        $scheduledTransaction->setStartDate(new \DateTime($data['startDate']));
        $scheduledTransaction->setEndDate(isset($data['endDate']) && !empty($data['endDate']) ? new \DateTime($data['endDate']) : null);
        $scheduledTransaction->setFrequency(FrequencyEnum::from($data['frequency']));

        if (isset($data['financialCategory'])) {
            $financialCategory = $financialCategoryRepository->find($data['financialCategory']);
            $this->denyAccessUnlessGranted('VIEW', $financialCategory);
            $scheduledTransaction->setFinancialCategory($financialCategory);
        }

        $entityManager->flush();

        return $this->json($scheduledTransaction, Response::HTTP_OK, [], ['groups' => ['scheduled_transaction_get', 'financial_category_get', 'financial_category_get_parent']]);
    }

    #[Route('/{scheduledTransaction}', name: 'app_api_scheduled_transaction_delete', methods: 'DELETE')]
    public function delete(BankAccount $bankAccount, ScheduledTransaction $scheduledTransaction, EntityManagerInterface $entityManager)
    {
        if ($this->isScheduledTransactionOnBankAccount($bankAccount, $scheduledTransaction)) {
            return $this->json(['error' => 'Scheduled Transaction is not linked to this bank account.'], Response::HTTP_BAD_REQUEST);
        }
        $this->denyAccessUnlessGranted('VIEW', $bankAccount);
        $this->denyAccessUnlessGranted('DELETE', $scheduledTransaction);
        $entityManager->remove($scheduledTransaction);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{scheduledTransaction}/cancel', name: 'app_api_scheduled_transaction_cancel', methods: 'DELETE')]
    public function cancel(Request $request, BankAccount $bankAccount, ScheduledTransaction $scheduledTransaction, EntityManagerInterface $entityManager, FinancialCategoryRepository $financialCategoryRepository)
    {
        if ($this->isScheduledTransactionOnBankAccount($bankAccount, $scheduledTransaction)) {
            return $this->json(['error' => 'Scheduled Transaction is not linked to this bank account.'], Response::HTTP_BAD_REQUEST);
        }
        $this->denyAccessUnlessGranted('VIEW', $bankAccount);
        $this->denyAccessUnlessGranted('EDIT', $scheduledTransaction);

        $data = json_decode($request->getContent(), true);
        $transaction = new Transaction();
        $transaction->setReference("");
        $transaction->setLabel($scheduledTransaction->getLabel());
        $transaction->setAmount(0);
        $transaction->setDate(new \DateTime($data['date']));
        $transaction->setBankAccount($bankAccount);
        $transaction->setScheduledTransaction($scheduledTransaction);

        $this->denyAccessUnlessGranted('VIEW', $scheduledTransaction->getFinancialCategory());
        $transaction->setFinancialCategory($scheduledTransaction->getFinancialCategory());

        $entityManager->persist($transaction);
        $entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    protected function isScheduledTransactionOnBankAccount(BankAccount $bankAccount, ScheduledTransaction $scheduledTransaction)
    {
        return ($scheduledTransaction->getBankAccount()->getId() !== $bankAccount->getId());
    }
}
