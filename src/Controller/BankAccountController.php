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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/bank/{bank}/bank-account', name: 'app_api_bank_account')]
class BankAccountController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/', name: 'app_api_bank_account_index', methods: 'GET')]
    public function index(Bank $bank)
    {
        $this->denyAccessUnlessGranted('VIEW', $bank);
        /** @var User $user */
        $user = $this->getUser();
        $bankAccounts = $user->getBankAccounts($bank);
        return $this->json($bankAccounts, 200, [], ['groups' => ['bank_account_get', 'user_bank_account_get', 'bank_get', 'user_get_join']]);
    }

    #[Route('/{bank_account}', name: 'app_api_bank_account_show', methods: 'GET')]
    public function show(Bank $bank, BankAccount $bank_account)
    {
        $this->denyAccessUnlessGranted('VIEW', $bank);
        $this->denyAccessUnlessGranted('VIEW', $bank_account);
        return $this->json($bank_account, 200, [], ['groups' => ['bank_account_get', 'bank_get', 'user_get_join']]);
    }

    #[Route('/', name: 'app_api_bank_account_create', methods: 'POST')]
    public function create(Request $request, Bank $bank, EntityManagerInterface $entityManager)
    {
        $data = json_decode($request->getContent(), true);

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
    public function edit(Request $request,Bank $bank, BankAccount $bank_account, EntityManagerInterface $entityManager)
    {
        $this->denyAccessUnlessGranted('EDIT', $bank_account);
        $data = json_decode($request->getContent(), true);

        $bank_account->setLabel($data['label']);
        $bank_account->setAccountNumber($data['account_number']);

        $entityManager->flush();

        return $this->json($bank_account, 200, [], ['groups' => ['bank_account_get', 'bank_get', 'user_get_join']]);
    }

    #[Route('/{bank_account}', name: 'app_api_bank_account_delete', methods: 'DELETE')]
    public function delete(Bank $bank, BankAccount $bank_account, EntityManagerInterface $entityManager)
    {
        $this->denyAccessUnlessGranted('DELETE', $bank_account);
        $entityManager->remove($bank_account);
        $entityManager->flush();

        return new JsonResponse(null, 204);
    }
}
