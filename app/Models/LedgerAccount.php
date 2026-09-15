<?php

namespace App\Models;

use App\Enums\JournalSide;
use App\Enums\LedgerAccountType;
use Database\Factories\LedgerAccountFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LedgerAccount extends Model
{
    /** @use HasFactory<LedgerAccountFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['code', 'name', 'account_type', 'normal_balance', 'is_active'];

    /**
     * @param  Builder<LedgerAccount>  $query
     * @return Builder<LedgerAccount>
     */
    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'account_type' => LedgerAccountType::class,
            'normal_balance' => JournalSide::class,
            'is_active' => 'boolean',
        ];
    }
}
