<?php
namespace App\DTO;

use Symfony\Component\Serializer\Annotation\Groups as Groups;

class BankAccountSummary
{
    #[Groups(["bank_account_summary_get"])]
    public float $credit = 0.0;
    #[Groups(["bank_account_summary_get"])]
    public float $debit = 0.0;
    #[Groups(["bank_account_summary_get"])]
    public float $realExpenses = 0.0;
    #[Groups(["bank_account_summary_get"])]
    public float $provisionalCredit = 0.0;
    #[Groups(["bank_account_summary_get"])]
    public float $provisionalDebit = 0.0;
    #[Groups(["bank_account_summary_get"])]
    public float $summary = 0.0;
    #[Groups(["bank_account_summary_get"])]
    public float $provisionalSummary = 0.0;
    #[Groups(["bank_account_summary_get"])]
    public float $startBalance = 0.0;
    #[Groups(["bank_account_summary_get"])]
    public float $provisionalStartBalance = 0.0;
    #[Groups(["bank_account_summary_get"])]
    public float $endBalance = 0.0;
    #[Groups(["bank_account_summary_get"])]
    public float $provisionalEndBalance = 0.0;

    public function __construct()
    {
    }

    /**
     * Get the value of credit
     */ 
    public function getCredit()
    {
        return $this->credit;
    }

    /**
     * Set the value of credit
     *
     * @return  self
     */ 
    public function setCredit($credit)
    {
        $this->credit = $credit;
        $this->calculate();

        return $this;
    }

    /**
     * Get the value of realExpenses
     */ 
    public function getRealExpenses()
    {
        return $this->realExpenses;
    }

    /**
     * Set the value of realExpenses
     *
     * @return  self
     */ 
    public function setRealExpenses($realExpenses)
    {
        $this->realExpenses = $realExpenses;

        return $this;
    }

    /**
     * Get the value of debit
     */ 
    public function getDebit()
    {
        return $this->debit;
    }

    /**
     * Set the value of debit
     *
     * @return  self
     */ 
    public function setDebit($debit)
    {
        $this->debit = $debit;
        $this->calculate();

        return $this;
    }

    /**
     * Get the value of provisionalCredit
     */ 
    public function getProvisionalCredit()
    {
        return $this->provisionalCredit;
    }

    /**
     * Set the value of provisionalCredit
     *
     * @return  self
     */ 
    public function setProvisionalCredit($provisionalCredit)
    {
        $this->provisionalCredit = $provisionalCredit;
        $this->calculate();
        return $this;
    }

    /**
     * Get the value of provisionalDebit
     */ 
    public function getProvisionalDebit()
    {
        return $this->provisionalDebit;
    }

    /**
     * Set the value of provisionalDebit
     *
     * @return  self
     */ 
    public function setProvisionalDebit($provisionalDebit)
    {
        $this->provisionalDebit = $provisionalDebit;
        $this->calculate();

        return $this;
    }

    /**
     * Get the value of summary
     */ 
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * Get the value of provisionalSummary
     */ 
    public function getProvisionalSummary()
    {
        return $this->provisionalSummary;
    }

    /**
     * Get the value of startBalance
     */ 
    public function getStartBalance()
    {
        return $this->startBalance;
    }

    /**
     * Set the value of startBalance
     *
     * @return  self
     */ 
    public function setStartBalance($startBalance)
    {
        $this->startBalance = $startBalance;
        $this->calculate();

        return $this;
    }

    /**
     * Get the value of startBalance
     */ 
    public function getProvisionalStartBalance()
    {
        return $this->provisionalStartBalance;
    }

    /**
     * Set the value of startBalance
     *
     * @return  self
     */ 
    public function setProvisionalStartBalance($provisionalStartBalance)
    {
        $this->provisionalStartBalance = $provisionalStartBalance;
        $this->calculate();

        return $this;
    }

    /**
     * Get the value of endBalance
     */ 
    public function getEndBalance()
    {
        return $this->endBalance;
    }

    /**
     * Set the value of endBalance
     *
     * @return  self
     */ 
    public function setEndBalance($endBalance)
    {
        $this->endBalance = $endBalance;
        $this->calculate();

        return $this;
    }

    /**
     * Get the value of provisionalEndBalance
     */ 
    public function getProvisionalEndBalance()
    {
        return $this->provisionalEndBalance;
    }

    private function calculate(){
        $this->summary = bcadd($this->credit, $this->debit, 2);
        $this->provisionalSummary =  bcadd($this->provisionalCredit, $this->provisionalDebit, 2);
        $this->endBalance =  bcadd($this->startBalance, $this->summary, 2);
        $this->provisionalEndBalance =  bcadd($this->provisionalStartBalance, $this->provisionalSummary, 2);
    }
}
