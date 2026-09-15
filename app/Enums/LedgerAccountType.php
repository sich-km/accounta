<?php

namespace App\Enums;

enum LedgerAccountType: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Revenue = 'revenue';
    case Expense = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::Asset => '資産',
            self::Liability => '負債',
            self::Equity => '純資産',
            self::Revenue => '収益',
            self::Expense => '費用',
        };
    }
}
