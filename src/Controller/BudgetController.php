<?php

namespace App\Controller;

use App\Entity\BankAccount;
use App\Entity\Budget;
use App\Repository\BudgetRepository;
use App\Repository\FinancialCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Enum\FrequencyEnum;
use App\Repository\ScheduledTransactionRepository;
use App\Repository\TransactionRepository;
use App\Service\BudgetService;
use App\Service\FinancialCategoryService;
use App\Service\ScheduledTransactionService;

#[Route('/bank-accounts/{bankAccount}/budget', name: 'app_api_budget')]
class BudgetController extends AbstractController
{
    #[Route('/', name: 'app_api_budget_index', methods: 'GET')]
    public function index(Request $request, BankAccount $bankAccount, BudgetRepository $budgetRepository)
    {
        $this->denyAccessUnlessGranted('VIEW', $bankAccount);

        $budgets = $budgetRepository->findBy(['bankAccount' => $bankAccount->getId()]);

        return $this->json($budgets, Response::HTTP_OK, [], ['groups' => ['budget_get', 'financial_category_get', 'financial_category_get_parent']]);
    }

    #[Route('/{budget}', name: 'app_api_budget_show', methods: 'GET', requirements: ['budget' => '\d+'])]
    public function show(BankAccount $bankAccount, Budget $budget)
    {
        if ($this->isBudgetOnBankAccount($bankAccount, $budget)) {
            return $this->json(['error' => 'Budget is not linked to this bank account.'], Response::HTTP_BAD_REQUEST);
        }
        $this->denyAccessUnlessGranted('VIEW', $bankAccount);
        $this->denyAccessUnlessGranted('VIEW', $budget);
        return $this->json($budget, Response::HTTP_OK, [], ['groups' => ['budget_get', 'financial_category_get', 'financial_category_get_parent']]);
    }

    #[Route('/', name: 'app_api_budget_create', methods: 'POST')]
    public function create(Request $request, BankAccount $bankAccount, EntityManagerInterface $entityManager, FinancialCategoryRepository $financialCategoryRepository)
    {
        $this->denyAccessUnlessGranted('EDIT', $bankAccount);
        $data = json_decode($request->getContent(), true);

        $budget = new Budget();
        $budget->setLabel($data['label']);
        $budget->setAmount($data['amount']);
        $budget->setStartDate(new \DateTime($data['startDate']));
        $budget->setEndDate(isset($data['endDate']) ? new \DateTime($data['endDate']) : null);
        $budget->setFrequency(FrequencyEnum::from($data['frequency']));
        $budget->setBankAccount($bankAccount);

        if (isset($data['financialCategory'])) {
            $financialCategory = $financialCategoryRepository->find($data['financialCategory']);
            $this->denyAccessUnlessGranted('VIEW', $financialCategory);
            $budget->setFinancialCategory($financialCategory);
        }

        $entityManager->persist($budget);
        $entityManager->flush();

        return $this->json($budget, Response::HTTP_CREATED, [], ['groups' => ['budget_get', 'financial_category_get', 'financial_category_get_parent']]);
    }

    #[Route('/{budget}', name: 'app_api_budget_edit', methods: 'PUT', requirements: ['budget' => '\d+'])]
    public function edit(Request $request, BankAccount $bankAccount, Budget $budget, EntityManagerInterface $entityManager, FinancialCategoryRepository $financialCategoryRepository)
    {
        if ($this->isBudgetOnBankAccount($bankAccount, $budget)) {
            return $this->json(['error' => 'Budget is not linked to this bank account.'], Response::HTTP_BAD_REQUEST);
        }
        $this->denyAccessUnlessGranted('VIEW', $bankAccount);
        $this->denyAccessUnlessGranted('EDIT', $budget);
        $data = json_decode($request->getContent(), true);

        $budget->setLabel($data['label']);
        $budget->setAmount($data['amount']);
        $budget->setStartDate(new \DateTime($data['startDate']));
        $budget->setEndDate(isset($data['endDate']) ? new \DateTime($data['endDate']) : null);
        $budget->setFrequency(FrequencyEnum::from($data['frequency']));

        if (isset($data['financialCategory'])) {
            $financialCategory = $financialCategoryRepository->find($data['financialCategory']);
            $this->denyAccessUnlessGranted('VIEW', $financialCategory);
            $budget->setFinancialCategory($financialCategory);
        }

        $entityManager->flush();

        return $this->json($budget, Response::HTTP_OK, [], ['groups' => ['budget_get', 'financial_category_get', 'financial_category_get_parent']]);
    }

    #[Route('/{budget}', name: 'app_api_budget_delete', methods: 'DELETE', requirements: ['budget' => '\d+'])]
    public function delete(BankAccount $bankAccount, Budget $budget, EntityManagerInterface $entityManager)
    {
        if ($this->isBudgetOnBankAccount($bankAccount, $budget)) {
            return $this->json(['error' => 'Budget is not linked to this bank account.'], Response::HTTP_BAD_REQUEST);
        }
        $this->denyAccessUnlessGranted('VIEW', $bankAccount);
        $this->denyAccessUnlessGranted('DELETE', $budget);
        $entityManager->remove($budget);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/overview', name: 'app_api_budget_overview', methods: 'GET')]
    public function overview(Request $request, BankAccount $bankAccount, BudgetService $budgetService): JsonResponse
    {
        $this->denyAccessUnlessGranted('VIEW', $bankAccount);
    
        $startDateInput = $request->query->get('start_date');
        $endDateInput = $request->query->get('end_date');
        $startDate = $startDateInput ? new \DateTime($startDateInput) : null;
        $endDate = $endDateInput ? new \DateTime($endDateInput) : null;
    
        if (!$startDate || !$endDate) {
            return $this->json(['error' => 'start_date and end_date required.'], Response::HTTP_BAD_REQUEST);
        }
    
        $budgetSummaries = $budgetService->calculateBudgetSummary($bankAccount, $startDate, $endDate);
    
        return $this->json($budgetSummaries, Response::HTTP_OK, [], ['groups' => ['budget_get', 'financial_category_get', 'financial_category_get_children', 'budget_summary_get']]);
    }

    protected function isBudgetOnBankAccount(BankAccount $bankAccount, Budget $budget)
    {
        return ($budget->getBankAccount()->getId() !== $bankAccount->getId());
    }
}
