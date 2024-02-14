<?php
namespace App\DTO;

use App\Entity\Budget;
use Symfony\Component\Serializer\Annotation\Groups as Groups;

class BudgetSummary
{
    #[Groups(["budget_summary_get"])]
    public Budget $budget;
    #[Groups(["budget_summary_get"])]
    public float $consumed = 0.0;
    #[Groups(["budget_summary_get"])]
    public float $provisionalConsumed = 0.0;
    #[Groups(["budget_summary_get"])]
    public float $summary = 0.0;
    #[Groups(["budget_summary_get"])]
    public float $provisionalSummary = 0.0;

    public function __construct(Budget $budget)
    {
        $this->budget = $budget;
    }
}
