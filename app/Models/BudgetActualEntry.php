<?php

namespace App\Models;

use Database\Factories\BudgetActualEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetActualEntry extends Model
{
    /** @use HasFactory<BudgetActualEntryFactory> */
    use HasFactory;

    /**
     * @var array<string, string>
     */
    public const TYPES = [
        'budget' => '予算',
        'actual' => '実績',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'department_id',
        'budget_actual_account_id',
        'period',
        'type',
        'amount',
        'memo',
    ];

    /**
     * @param  Builder<BudgetActualEntry>  $query
     * @return Builder<BudgetActualEntry>
     */
    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where($this->qualifyColumn('organization_id'), $organizationId);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function budgetActualAccount(): BelongsTo
    {
        return $this->belongsTo(BudgetActualAccount::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period' => 'date',
            'amount' => 'decimal:2',
        ];
    }
}
