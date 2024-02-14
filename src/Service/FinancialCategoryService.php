<?php
// src/Service/FinancialCategoryService.php

namespace App\Service;

use App\Entity\FinancialCategory;
use App\Repository\FinancialCategoryRepository;
use Symfony\Bundle\SecurityBundle\Security;

class FinancialCategoryService
{
    private $security;
    private $financialCategoryRepository;

    public function __construct(Security $security, FinancialCategoryRepository $financialCategoryRepository)
    {
        $this->security = $security;
        $this->financialCategoryRepository = $financialCategoryRepository;
    }

    /** @param FinancialCategory[] $financialCategories */
    public function getOrganizeFinancialCategories(FinancialCategory $root = null): array
    {

        return array_filter($this->financialCategoryRepository->findBy(['user' => [$this->security->getUser(), null]]), function ($financialCategory) use ($root) {
            /** @var FinancialCategory $financialCategory */
            return $root ? $financialCategory->getParent() === $root : !$financialCategory->getParent();
        });
    }

    /**
     * Get a flat array of all financial categories accessible to the current user starting from an optional root category,
     * including children of all levels, without hierarchy.
     *
     * @param FinancialCategory|null $rootCategory The root category to start from, or null to include all top-level categories.
     * @return FinancialCategory[] Array of all accessible financial categories.
     */
    public function getAllAccessibleFinancialCategoriesFlat(?FinancialCategory $rootCategory = null): array
    {
        $allCategories = [];

        if ($rootCategory === null) {
            // No rootCategory specified, fetch top-level categories associated with the user or that are public
            $rootCategories = $this->financialCategoryRepository->findBy([
                'user' => [$this->security->getUser(), null],
                'parent' => null
            ]);

            foreach ($rootCategories as $category) {
                $this->addAllChildCategories($category, $allCategories);
            }
        } else {
            // Start from the specified rootCategory
            $this->addAllChildCategories($rootCategory, $allCategories);
        }

        return $allCategories;
    }

    /**
     * Recursively adds a category and all of its child categories to the provided array.
     *
     * @param FinancialCategory $category The category to add.
     * @param array $allCategories Reference to the array to which categories are added.
     */
    private function addAllChildCategories(FinancialCategory $category, array &$allCategories): void
    {
        $allCategories[] = $category;
        foreach ($category->getChildren() as $child) {
            /** @var FinancialCategory $child */
            $this->addAllChildCategories($child, $allCategories);
        }
    }
}
