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

    public static function expenseTypes(): array
    {
        return [
            self::EssentialFixedExpense,
            self::EssentialVariableExpense,
            self::NonEssentialFlexibleExpense,
            self::DebtRepayment,
            self::DonationCharity,
        ];
    }

    public static function essentialExpenses(): array
    {
        return [
            self::EssentialFixedExpense,
            self::EssentialVariableExpense,
        ];
    }

    public static function nonEssentialExpenses(): array
    {
        return [
            self::NonEssentialFlexibleExpense,
            self::DebtRepayment,
            self::DonationCharity,
        ];
    }
}
