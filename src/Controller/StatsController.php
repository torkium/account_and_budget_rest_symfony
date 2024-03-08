<?php

namespace App\Controller;

use App\Repository\BankAccountRepository;
use App\Repository\FinancialCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\StatsService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Response;

#[Route('/stats', name: 'app_api_stats')]
class StatsController extends AbstractController
{
    #[Route('/annual-incomes-by-month/{startDate}/{endDate}', name: 'app_api_stats_annual_incomes_by_month', methods: 'GET', requirements: ["startDate" => "\d{4}-\d{2}-\d{2}", "endDate" => "\d{4}-\d{2}-\d{2}"])]
    public function annualIncomesByMonth(Request $request, \DateTime $startDate, \DateTime $endDate, StatsService $statsService, BankAccountRepository $bankAccountRepository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $bankAccountFilter = $request->query->get('bank_account');
        $bankAccount = $bankAccountFilter ? $bankAccountRepository->findOneBy(["id" => $bankAccountFilter]) : null;
        if($bankAccount){
            $this->denyAccessUnlessGranted('VIEW', $bankAccount);
        }
        $bankAccounts = $bankAccount ? new ArrayCollection([$bankAccount]) : $user->getBankAccounts();
        $stats = $statsService->getAnnualIncomesByMonth($bankAccounts, $startDate, $endDate);
        return $this->json($stats, Response::HTTP_OK, [], ['groups' => ["stats_get_values_for_month"]]);
    }
    #[Route('/annual-expenses-by-month/{startDate}/{endDate}', name: 'app_api_stats_annual_expenses_by_month', methods: 'GET', requirements: ["startDate" => "\d{4}-\d{2}-\d{2}", "endDate" => "\d{4}-\d{2}-\d{2}"])]
    public function annualExpensesByMonth(Request $request, \DateTime $startDate, \DateTime $endDate, StatsService $statsService, BankAccountRepository $bankAccountRepository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $bankAccountFilter = $request->query->get('bank_account');
        $bankAccount = $bankAccountFilter ? $bankAccountRepository->findOneBy(["id" => $bankAccountFilter]) : null;
        if($bankAccount){
            $this->denyAccessUnlessGranted('VIEW', $bankAccount);
        }
        $bankAccounts = $bankAccount ? new ArrayCollection([$bankAccount]) : $user->getBankAccounts();
        $stats = $statsService->getAnnualExpensesByMonth($bankAccounts, $startDate, $endDate);
        return $this->json($stats, Response::HTTP_OK, [], ['groups' => ["stats_get_values_for_month"]]);
    }
    #[Route('/annual-values-by-category-by-month/{startDate}/{endDate}', name: 'app_api_stats_annual_values_by_category_by_month', methods: 'GET', requirements: ["startDate" => "\d{4}-\d{2}-\d{2}", "endDate" => "\d{4}-\d{2}-\d{2}"])]
    public function annualValuesByCategoryByMonth(Request $request, \DateTime $startDate, \DateTime $endDate, StatsService $statsService, FinancialCategoryRepository $financialCategoryRepository, BankAccountRepository $bankAccountRepository): JsonResponse
    {
        $rootCategoryFilter = $request->query->get('root_category');
        $rootCategory = $rootCategoryFilter ? $financialCategoryRepository->findOneBy(["id" => $rootCategoryFilter]) : null;
        /** @var User $user */
        $user = $this->getUser();
        $bankAccountFilter = $request->query->get('bank_account');
        $bankAccount = $bankAccountFilter ? $bankAccountRepository->findOneBy(["id" => $bankAccountFilter]) : null;
        if($bankAccount){
            $this->denyAccessUnlessGranted('VIEW', $bankAccount);
        }
        $bankAccounts = $bankAccount ? new ArrayCollection([$bankAccount]) : $user->getBankAccounts();
        $stats = $statsService->getAnnualValuesByCategoryByMonth($bankAccounts, $startDate, $endDate, $rootCategory);
        return $this->json($stats, Response::HTTP_OK, [], ['groups' => [""]]);
    }
    #[Route('/annual-expenses-by-category-by-month/{startDate}/{endDate}', name: 'app_api_stats_annual_expenses_by_category_by_month', methods: 'GET', requirements: ["startDate" => "\d{4}-\d{2}-\d{2}", "endDate" => "\d{4}-\d{2}-\d{2}"])]
    public function annualExpensesByCategoryByMonth(Request $request, \DateTime $startDate, \DateTime $endDate, StatsService $statsService, FinancialCategoryRepository $financialCategoryRepository, BankAccountRepository $bankAccountRepository): JsonResponse
    {
        $rootCategoryFilter = $request->query->get('root_category');
        $rootCategory = $rootCategoryFilter ? $financialCategoryRepository->findOneBy(["id" => $rootCategoryFilter]) : null;
        /** @var User $user */
        $user = $this->getUser();
        $bankAccountFilter = $request->query->get('bank_account');
        $bankAccount = $bankAccountFilter ? $bankAccountRepository->findOneBy(["id" => $bankAccountFilter]) : null;
        if($bankAccount){
            $this->denyAccessUnlessGranted('VIEW', $bankAccount);
        }
        $bankAccounts = $bankAccount ? new ArrayCollection([$bankAccount]) : $user->getBankAccounts();
        $stats = $statsService->getAnnualExpensesByCategoryByMonth($bankAccounts, $startDate, $endDate, $rootCategory);
        return $this->json($stats, Response::HTTP_OK, [], ['groups' => [""]]);
    }
    #[Route('/annual-expenses-by-category/{startDate}/{endDate}', name: 'app_api_stats_annual_values_by_category', methods: 'GET', requirements: ["startDate" => "\d{4}-\d{2}-\d{2}", "endDate" => "\d{4}-\d{2}-\d{2}"])]
    public function annualExpensesByCategory(Request $request, \DateTime $startDate, \DateTime $endDate, StatsService $statsService, FinancialCategoryRepository $financialCategoryRepository, BankAccountRepository $bankAccountRepository): JsonResponse
    {
        $rootCategoryFilter = $request->query->get('root_category');
        $rootCategory = $rootCategoryFilter ? $financialCategoryRepository->findOneBy(["id" => $rootCategoryFilter]) : null;
        /** @var User $user */
        $user = $this->getUser();
        $bankAccountFilter = $request->query->get('bank_account');
        $bankAccount = $bankAccountFilter ? $bankAccountRepository->findOneBy(["id" => $bankAccountFilter]) : null;
        if($bankAccount){
            $this->denyAccessUnlessGranted('VIEW', $bankAccount);
        }
        $bankAccounts = $bankAccount ? new ArrayCollection([$bankAccount]) : $user->getBankAccounts();
        $stats = $statsService->getAnnuaExpensesByCategory($bankAccounts, $startDate, $endDate, $rootCategory);
        return $this->json($stats, Response::HTTP_OK, [], ['groups' => [""]]);
    }
    #[Route('/annual-bank-balance-evolution/{startDate}/{endDate}', name: 'app_api_stats_annual_bank_balance_evolution', methods: 'GET', requirements: ["startDate" => "\d{4}-\d{2}-\d{2}", "endDate" => "\d{4}-\d{2}-\d{2}"])]
    public function annualBankBalanceEvolution(Request $request, \DateTime $startDate, \DateTime $endDate, StatsService $statsService, BankAccountRepository $bankAccountRepository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $bankAccountFilter = $request->query->get('bank_account');
        $bankAccount = $bankAccountFilter ? $bankAccountRepository->findOneBy(["id" => $bankAccountFilter]) : null;
        if($bankAccount){
            $this->denyAccessUnlessGranted('VIEW', $bankAccount);
        }
        $bankAccounts = $bankAccount ? new ArrayCollection([$bankAccount]) : $user->getBankAccounts();
        $stats = $statsService->getAnnualBalanceEvolutionByMonth($bankAccounts, $startDate, $endDate);
        return $this->json($stats, Response::HTTP_OK, [], ['groups' => ["stats_get_values_for_month"]]);
    }
}
