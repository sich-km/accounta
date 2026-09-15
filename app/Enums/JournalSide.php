<?php

namespace App\Enums;

enum JournalSide: string
{
    case Debit = 'debit';
    case Credit = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::Debit => '借方',
            self::Credit => '貸方',
        };
    }
}
