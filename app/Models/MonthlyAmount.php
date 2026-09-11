<?php

namespace App\Models;

use Database\Factories\MonthlyAmountFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyAmount extends Model
{
    /** @use HasFactory<MonthlyAmountFactory> */
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
        'management_account_id',
        'period',
        'type',
        'amount',
        'memo',
    ];

    /**
     * @param  Builder<MonthlyAmount>  $query
     * @return Builder<MonthlyAmount>
     */
    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function managementAccount(): BelongsTo
    {
        return $this->belongsTo(ManagementAccount::class);
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
