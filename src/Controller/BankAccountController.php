<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Bank;
use App\Entity\BankAccount;
use App\Entity\User;
use App\Entity\UserBankAccount;
use App\Enum\PermissionEnum;
use App\Repository\BankRepository;
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
    public function edit(Request $request, BankAccount $bank_account, EntityManagerInterface $entityManager)
    {
        $this->denyAccessUnlessGranted('EDIT', $bank_account);
        $data = json_decode($request->getContent(), true);

        $bank_account->setLabel($data['label']);
        $bank_account->setAccountNumber($data['account_number']);

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
}
