<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\FinancialCategory;
use App\Enum\FinancialCategoryTypeEnum;
use App\Repository\FinancialCategoryRepository;
use App\Service\FinancialCategoryService;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/financial-categories', name: 'app_api_financial_category')]
class FinancialCategoryController extends AbstractController
{

    #[Route('/', name: 'app_api_financial_category_index', methods: 'GET')]
    public function index(FinancialCategoryService $financialCategoryService)
    {
        $hierarchicalFinancialCategories = $financialCategoryService->getOrganizeFinancialCategories();

        return $this->json($hierarchicalFinancialCategories, 200, [], ['groups' => ['financial_category_get', 'financial_category_get_parent_id', 'financial_category_get_children']]);
    }

    #[Route('/{id}', name: 'app_api_financial_category_show', methods: 'GET')]
    public function show(FinancialCategory $financialCategory)
    {
        $this->denyAccessUnlessGranted('VIEW', $financialCategory);

        return $this->json($financialCategory, 200, [], ['groups' => ['financial_category_get', 'financial_category_get_children', 'financial_category_get_parent_id']]);
    }

    #[Route('/', name: 'app_api_financial_category_create', methods: 'POST')]
    public function create(Request $request, EntityManagerInterface $entityManager, FinancialCategoryRepository $financialCategoryRepository)
    {
        $data = json_decode($request->getContent(), true);
        $parent = null;
        if (array_key_exists("parent_id", $data) && !empty($data['parent_id'])) {
            $parent = $financialCategoryRepository->findOneBy(['id' => $data['parent_id']]);
            $this->denyAccessUnlessGranted('VIEW', $parent);
        }
        $financialCategory = new FinancialCategory();
        $financialCategory->setLabel($data['label']);
        $financialCategory->setParent($parent);
        $financialCategory->setType(FinancialCategoryTypeEnum::from($data['type']));
        $financialCategory->setUser($this->geTUser());
        $entityManager->persist($financialCategory);
        $entityManager->flush();

        return $this->json($financialCategory, 201, [], ['groups' => ['financial_category_get', 'financial_category_get_children', 'financial_category_get_parent_id']]);
    }

    #[Route('/{id}', name: 'app_api_financial_category_edit', methods: 'PUT')]
    public function edit(Request $request, FinancialCategory $financialCategory, EntityManagerInterface $entityManager, FinancialCategoryRepository $financialCategoryRepository)
    {
        $this->denyAccessUnlessGranted('EDIT', $financialCategory);
        $data = json_decode($request->getContent(), true);

        $parent = null;
        if (array_key_exists("parent_id", $data) && $data['parent_id']) {
            $parent = $financialCategoryRepository->findOneBy(['id' => $data['parent_id']]);
            $this->denyAccessUnlessGranted('VIEW', $parent);
        }

        $financialCategory->setLabel($data['label']);
        $financialCategory->setType(FinancialCategoryTypeEnum::from($data['type']));
        $financialCategory->setParent($parent);

        $entityManager->flush();

        return $this->json($financialCategory, 200, [], ['groups' => ['financial_category_get', 'financial_category_get_children', 'financial_category_get_parent_id']]);
    }

    #[Route('/{id}', name: 'app_api_financial_category_delete', methods: 'DELETE')]
    public function delete(FinancialCategory $financialCategory, EntityManagerInterface $entityManager)
    {
        $this->denyAccessUnlessGranted('DELETE', $financialCategory);
        $entityManager->remove($financialCategory);
        $entityManager->flush();

        return new JsonResponse(null, 204);
    }
}
