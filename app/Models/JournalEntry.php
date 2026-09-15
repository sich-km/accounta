<?php

namespace App\Models;

use App\Enums\JournalSide;
use Database\Factories\JournalEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    /** @use HasFactory<JournalEntryFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['originating_department_id', 'entry_date', 'description', 'notes'];

    /**
     * @param  Builder<JournalEntry>  $query
     * @return Builder<JournalEntry>
     */
    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function originatingDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'originating_department_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class)->orderBy('line_number');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(JournalDocument::class)->orderBy('id');
    }

    public function debitTotal(): string
    {
        return $this->totalFor(JournalSide::Debit);
    }

    public function creditTotal(): string
    {
        return $this->totalFor(JournalSide::Credit);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['entry_date' => 'date'];
    }

    private function totalFor(JournalSide $side): string
    {
        /** @var Collection<int, JournalEntryLine> $lines */
        $lines = $this->relationLoaded('lines') ? $this->lines : $this->lines()->get();
        $totalInCents = $lines
            ->where('side', $side)
            ->sum(fn (JournalEntryLine $line): int => self::amountToCents($line->amount));

        return sprintf('%d.%02d', intdiv($totalInCents, 100), $totalInCents % 100);
    }

    private static function amountToCents(string $amount): int
    {
        [$integerPart, $decimalPart] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $integerPart * 100) + (int) str_pad($decimalPart, 2, '0');
    }
}
