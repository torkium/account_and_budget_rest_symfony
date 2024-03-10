<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\BankAccount;
use App\Entity\User;
use App\Entity\UserBankAccount;
use App\Enum\PermissionEnum;
use App\Repository\BankAccountRepository;
use App\Repository\BankRepository;
use App\Service\BankAccountService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

#[Route('/bank-accounts', name: 'app_api_bank_account')]
class BankAccountController extends AbstractController
{

    #[Route('/', name: 'app_api_bank_account_index', methods: 'GET')]
    public function index(Request $request, BankRepository $bankRepository)
    {
        $bankId = $request->query->get('bank_id');
        $bank = $bankId ? $bankRepository->findOneBy(['id' => $bankId]) : null;
        if($bank){
            $this->denyAccessUnlessGranted('VIEW', $bank);
        }
        /** @var User $user */
        $user = $this->getUser();
        $bankAccounts = $user->getBankAccounts($bank);
        return $this->json($bankAccounts, 200, [], ['groups' => ['bank_account_get', 'user_bank_account_get', 'bank_get', 'user_get_join']]);
    }

    #[Route('/{bank_account}', name: 'app_api_bank_account_show', methods: 'GET')]
    public function show(BankAccount $bank_account)
    {
        $this->denyAccessUnlessGranted('VIEW', $bank_account);
        return $this->json($bank_account, 200, [], ['groups' => ['bank_account_get', 'bank_get', 'user_get_join']]);
    }

    #[Route('/', name: 'app_api_bank_account_create', methods: 'POST')]
    public function create(Request $request, EntityManagerInterface $entityManager, BankRepository $bankRepository)
    {
        $data = json_decode($request->getContent(), true);

        $bank = $data['bank_id'] ? $bankRepository->findOneBy(['id' => $data['bank_id']]) : null;
        if(!$bank){
            return $this->json(['error' => 'bank_id required.'], Response::HTTP_BAD_REQUEST);
        }
        
        $this->denyAccessUnlessGranted('VIEW', $bank);
        
        $bankAccount = new BankAccount();
        $bankAccount->setLabel($data['label']);
        $bankAccount->setAccountNumber($data['account_number']);
        $bankAccount->setInitialAmount($data['initial_amount'] ?? 0);
        $bankAccount->setBank($bank);

        $userBankAccount = new UserBankAccount();
        $userBankAccount->setUser($this->getUser());
        $userBankAccount->setBankAccount($bankAccount);
        $userBankAccount->setPermissions(PermissionEnum::ADMIN);
        $entityManager->persist($userBankAccount);

        $bankAccount->addUserBankAccount($userBankAccount);

        $entityManager->persist($bankAccount);
        $entityManager->flush();

        return $this->json($bankAccount, 201, [], ['groups' => ['bank_account_get', 'user_bank_account_get', 'bank_get', 'user_get_join']]);
    }

    #[Route('/{bank_account}', name: 'app_api_bank_account_edit', methods: 'PUT')]
    public function edit(Request $request, BankAccount $bank_account, EntityManagerInterface $entityManager, BankRepository $bankRepository)
    {
        $this->denyAccessUnlessGranted('EDIT', $bank_account);
        $data = json_decode($request->getContent(), true);

        if(array_key_exists("bank_id", $data)){
            $newBank = $bankRepository->findOneBy(['id' => $data['bank_id']]);
            $this->denyAccessUnlessGranted('VIEW', $newBank);
            $bank_account->setBank($newBank);
        }
        $bank_account->setLabel($data['label']);
        $bank_account->setAccountNumber($data['account_number']);
        $bank_account->setInitialAmount($data['initial_amount']) ?? 0;

        $entityManager->flush();

        return $this->json($bank_account, 200, [], ['groups' => ['bank_account_get', 'bank_get', 'user_get_join']]);
    }

    #[Route('/{bank_account}', name: 'app_api_bank_account_delete', methods: 'DELETE')]
    public function delete(BankAccount $bank_account, EntityManagerInterface $entityManager)
    {
        $this->denyAccessUnlessGranted('DELETE', $bank_account);
        $entityManager->remove($bank_account);
        $entityManager->flush();

        return new JsonResponse(null, 204);
    }

    #[Route('/{bank_account}/overview', name: 'app_api_bank_account_overview', methods: 'GET')]
    public function overview(Request $request, BankAccount $bank_account, BankAccountService $bankAccountService): JsonResponse
    {
        $this->denyAccessUnlessGranted('VIEW', $bank_account);
    
        $startDateInput = $request->query->get('start_date');
        $endDateInput = $request->query->get('end_date');
        $startDate = $startDateInput ? new \DateTime($startDateInput) : null;
        $endDate = $endDateInput ? new \DateTime($endDateInput) : null;
    
        if (!$startDate || !$endDate) {
            return $this->json(['error' => 'start_date and end_date required.'], Response::HTTP_BAD_REQUEST);
        }
    
        $bankAccountSummaries = $bankAccountService->calculateBankAccountSummary($bank_account, $startDate, $endDate);
    
        return $this->json($bankAccountSummaries, Response::HTTP_OK, [], ['groups' => ['bank_account_summary_get']]);
    }

    #[Route('/{bank_account}/init-balance', name: 'app_api_bank_account_init_balance', methods: 'PUT')]
    public function initBalance(Request $request, BankAccount $bank_account, BankAccountRepository $bankAccountRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('EDIT', $bank_account);
    
        $data = json_decode($request->getContent(), true);
        
        if(!array_key_exists("actual_balance", $data)){
            return $this->json(['error' => 'actual_balance required.'], Response::HTTP_BAD_REQUEST);
        }
        $actual_balance = $data["actual_balance"];
        $bank_account->setInitialAmount(bcsub($actual_balance,$bankAccountRepository->getTotalTransactions($bank_account), 2));
        $entityManager->persist($bank_account);
        $entityManager->flush();
        return $this->json($bank_account, Response::HTTP_OK, [], ['groups' => ['bank_account_get', 'bank_get', 'user_get_join']]);
    }
}
