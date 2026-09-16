<?php

namespace App\Models;

use Database\Factories\BudgetActualAccountFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetActualAccount extends Model
{
    /** @use HasFactory<BudgetActualAccountFactory> */
    use HasFactory;

    /**
     * @var array<string, string>
     */
    public const TYPES = [
        'asset' => '資産',
        'liability' => '負債',
        'equity' => '純資産',
        'revenue' => '収益',
        'expense' => '費用',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'account_type',
        'is_active',
    ];

    /**
     * @param  Builder<BudgetActualAccount>  $query
     * @return Builder<BudgetActualAccount>
     */
    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function budgetActualEntries(): HasMany
    {
        return $this->hasMany(BudgetActualEntry::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
