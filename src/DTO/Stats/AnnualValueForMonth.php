<?php
namespace App\DTO\Stats;

use Symfony\Component\Serializer\Annotation\Groups as Groups;

class AnnualValueForMonth
{
    #[Groups(["stats_get_annual_income"])]
    public float $amount = 0.0;

    #[Groups(["stats_get_annual_income"])]
    public string $month = "";

    public function __construct(float $amount, string $month){
        $this->amount = $amount;
        $this->month = $month;
    }
}