<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use InvalidArgumentException;

class FiscalYearService
{
    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable}
     */
    public function period(int $fiscalYear, int $fiscalYearStartMonth): array
    {
        $this->validateStartMonth($fiscalYearStartMonth);

        $start = CarbonImmutable::create($fiscalYear, $fiscalYearStartMonth, 1)->startOfDay();

        return [
            'start' => $start,
            'end' => $start->addYear()->subDay()->endOfDay(),
        ];
    }

    public function current(int $fiscalYearStartMonth, ?CarbonImmutable $asOf = null): int
    {
        $this->validateStartMonth($fiscalYearStartMonth);

        $asOf ??= CarbonImmutable::today();

        return $asOf->month >= $fiscalYearStartMonth
            ? $asOf->year
            : $asOf->year - 1;
    }

    public function forDate(DateTimeInterface|string $date, int $fiscalYearStartMonth): int
    {
        $this->validateStartMonth($fiscalYearStartMonth);

        $date = CarbonImmutable::parse($date);

        return $date->month >= $fiscalYearStartMonth
            ? $date->year
            : $date->year - 1;
    }

    /**
     * @param  iterable<int, DateTimeInterface|string|null>  $dates
     * @return list<int>
     */
    public function availableYears(
        iterable $dates,
        int $fiscalYearStartMonth,
        ?CarbonImmutable $asOf = null,
    ): array {
        $years = [$this->current($fiscalYearStartMonth, $asOf)];

        foreach ($dates as $date) {
            if ($date !== null) {
                $years[] = $this->forDate($date, $fiscalYearStartMonth);
            }
        }

        $years = array_values(array_unique($years));
        rsort($years, SORT_NUMERIC);

        return $years;
    }

    private function validateStartMonth(int $fiscalYearStartMonth): void
    {
        if ($fiscalYearStartMonth < 1 || $fiscalYearStartMonth > 12) {
            throw new InvalidArgumentException('The fiscal year start month must be between 1 and 12.');
        }
    }
}
