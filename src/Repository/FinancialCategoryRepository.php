<?php

namespace App\Repository;

use App\Entity\FinancialCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Profile>
 *
 * @method FinancialCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method FinancialCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method FinancialCategory[]    findAll()
 * @method FinancialCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FinancialCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FinancialCategory::class);
    }
}
