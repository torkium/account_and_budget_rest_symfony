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

        return array_filter($this->financialCategoryRepository->findBy(['user' => [$this->security->getUser(), null]]), function ($financialCategory) use ($root){
            /** @var FinancialCategory $financialCategory */
            return $root ? $financialCategory->getParent() === $root : !$financialCategory->getParent();
        });
    }
}
