<?php

namespace App\Controller;

use App\Entity\BankAccount;
use App\Entity\Transaction;
use App\Repository\FinancialCategoryRepository;
use App\Repository\ScheduledTransactionRepository;
use App\Repository\TransactionRepository;
use App\Service\FileParserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bank-accounts/{bankAccount}/import', name: 'app_api_bank_account_import')]
class TransactionsImportController extends AbstractController
{
    private $csvParserService;

    public function __construct(FileParserService $csvParserService)
    {
        $this->csvParserService = $csvParserService;
    }

    #[Route('/request-headers', name: 'app_api_bank_account_import_request_headers', methods: 'POST')]
    public function requestHeaders(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $file = $request->files->get('csv_file');

        if (!$file) {
            return $this->json(['error' => 'No file uploaded'], Response::HTTP_BAD_REQUEST);
        }

        $parsedDatas = $this->csvParserService->parseFile($file);

        if ($parsedDatas['headers'] === [] || $parsedDatas['datas'] === []) {
            return $this->json(['error' => 'Unable to parse CSV file'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($parsedDatas['headers']);
    }

    #[Route('/request-transactions', name: 'app_api_bank_account_import_request_transactions', methods: 'POST')]
    public function requestTransactions(Request $request, BankAccount $bankAccount, TransactionRepository $transactionRepository): JsonResponse
    {
        $file = $request->files->get('csv_file');
        if (!$file) {
            return $this->json(['error' => 'No file uploaded'], Response::HTTP_BAD_REQUEST);
        }

        $headers = [
            'headers_date' => $request->request->get('headers_date'),
            'headers_libelle' => $request->request->get('headers_libelle'),
            'headers_amount' => $request->request->get('headers_amount'),
        ];
        if (!$this->isValidHeaders($headers)) {
            return $this->json(['error' => 'Missing required headers'], Response::HTTP_BAD_REQUEST);
        }

        $parsedDatas = $this->csvParserService->parseFile($file);
        if (empty($parsedDatas['headers']) || empty($parsedDatas['datas'])) {
            return $this->json(['error' => 'Unable to parse CSV file'], Response::HTTP_BAD_REQUEST);
        }

        $references = $this->generateReferences($parsedDatas['datas'], $headers, $bankAccount);

        $transactions = $transactionRepository->findBy(['reference' => $references]);

        $references_matched = array_map(function ($transaction) {
            return $transaction->getReference();
        }, $transactions);

        $parsedDatas['datas'] = array_filter($parsedDatas['datas'], function ($data) use ($references_matched) {
            return !in_array($data['reference'], $references_matched);
        });

        $transformedDatas = array_map(function ($data) use ($headers) {
            return [
                'id' => null,
                'reference' => $data['reference'],
                'date' => $data[$headers['headers_date']],
                'label' => $data[$headers['headers_libelle']],
                'amount' => (float) $data[$headers['headers_amount']],
            ];
        }, $parsedDatas['datas']);

        return $this->json(array_values($transformedDatas));

        return $this->json(array_values($parsedDatas['datas']));
    }

    #[Route('', name: 'app_api_bank_account_import', methods: 'POST')]
    public function import(Request $request, EntityManagerInterface $entityManager, BankAccount $bankAccount, FinancialCategoryRepository $financialCategoryRepository, ScheduledTransactionRepository $scheduledTransactionRepository)
    {
        $this->denyAccessUnlessGranted('EDIT', $bankAccount);
        $data = json_decode($request->getContent(), true);
        $transactions = $data['transactions'] ?? [];

        $response = ['imported' => 0, 'errors' => 0, 'errorDetails' => []];

        foreach ($transactions as $transactionData) {
            try {
                $transaction = new Transaction();
                $transaction->setReference($transactionData['reference'] ?? null);
                $transaction->setLabel($transactionData['label']);
                $transaction->setAmount($transactionData['amount']);
                $transaction->setDate(new \DateTime($transactionData['date']));
                $transaction->setBankAccount($bankAccount);

                if (isset($transactionData['financialCategory']) && !empty($transactionData['financialCategory'])) {
                    $financialCategory = $financialCategoryRepository->find($transactionData['financialCategory']);
                    $this->denyAccessUnlessGranted('VIEW', $financialCategory);
                    $transaction->setFinancialCategory($financialCategory);
                }

                if (isset($transactionData['scheduledTransactionId']) && !empty($transactionData['scheduledTransactionId'])) {
                    $scheduledTransaction = $scheduledTransactionRepository->find($transactionData['scheduledTransactionId']);
                    if ($scheduledTransaction && $scheduledTransaction->getBankAccount()->getId() === $transaction->getBankAccount()->getId() && $scheduledTransaction->getFinancialCategory()->getId() === $transaction->getFinancialCategory()->getId()) {
                        $this->denyAccessUnlessGranted('VIEW', $scheduledTransaction);
                        $transaction->setScheduledTransaction($scheduledTransaction);
                    } else {
                        throw new \Exception('ScheduledTransaction validation failed.');
                    }
                }

                $entityManager->persist($transaction);
                $response['imported']++;
            } catch (\Exception $e) {
                $response['errors']++;
                $response['errorDetails'][] = $e->getMessage();
            }
        }

        $entityManager->flush();

        return $this->json($response, 201);
    }

    private function isValidHeaders($data): bool
    {
        return isset($data['headers_date'], $data['headers_libelle'], $data['headers_amount']);
    }

    private function generateReferences(&$datas, $headers, BankAccount $bankAccount): array
    {
        $references = [];
        foreach ($datas as &$data) {
            $reference = $bankAccount->getId() . "_" . $data[$headers['headers_date']] . "_" . $data[$headers['headers_libelle']] . "_" . $data[$headers['headers_amount']];
            while (in_array($reference, $references)) {
                $i = 1;
                $references .= "_" . $i;
                $i++;
            }
            $data['reference'] = $reference;
            $references[] = $reference;
        }
        return $references;
    }
}
