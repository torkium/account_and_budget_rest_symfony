<?php

namespace App\Enum;

enum FinancialCategoryTypeEnum: string
{
    case Undefined = '';
    case EssentialFixedExpense = 'EssentialFixedExpense';
    case EssentialVariableExpense = 'EssentialVariableExpense';
    case NonEssentialFlexibleExpense = 'NonEssentialFlexibleExpense';
    case Savings = 'Savings';
    case Investment = 'Investment';
    case Internal = 'Internal';
    case Income = 'Income';
    case DebtRepayment = 'DebtRepayment';
    case DonationCharity = 'DonationCharity';
}
