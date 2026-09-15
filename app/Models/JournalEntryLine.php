<?php

namespace App\Models;

use App\Enums\JournalSide;
use Database\Factories\JournalEntryLineFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntryLine extends Model
{
    /** @use HasFactory<JournalEntryLineFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'organization_id',
        'line_number',
        'ledger_account_id',
        'department_id',
        'side',
        'amount',
        'description',
    ];

    /**
     * @param  Builder<JournalEntryLine>  $query
     * @return Builder<JournalEntryLine>
     */
    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('journal_entry_lines.organization_id', $organizationId);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'side' => JournalSide::class,
            'amount' => 'decimal:2',
        ];
    }
}
