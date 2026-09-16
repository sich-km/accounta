<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class DashboardFiscalYearPreferenceService
{
    private const string CACHE_PREFIX = 'dashboard:fiscal-year-preferences:user:';

    public function getFiscalYear(User $user, string $section, int $defaultFiscalYear): int
    {
        $storedFiscalYear = filter_var(
            Cache::get($this->cacheKey($user, $section)),
            FILTER_VALIDATE_INT,
        );

        return $storedFiscalYear === false
            ? $defaultFiscalYear
            : $storedFiscalYear;
    }

    public function putFiscalYear(User $user, string $section, int $fiscalYear): void
    {
        $cacheKey = $this->cacheKey($user, $section);

        if (Cache::get($cacheKey) !== $fiscalYear) {
            Cache::forever($cacheKey, $fiscalYear);
        }
    }

    private function cacheKey(User $user, string $section): string
    {
        return self::CACHE_PREFIX.$user->getAuthIdentifier().':'.$section;
    }
}
