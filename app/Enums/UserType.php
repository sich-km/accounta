<?php

namespace App\Enums;

enum UserType: string
{
    case Admin = 'admin';
    case CompanyAdmin = 'company_admin';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => '管理者',
            self::CompanyAdmin => '会社管理者',
            self::User => '一般ユーザー',
        };
    }
}
