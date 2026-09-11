<?php

namespace App\Models;

use Database\Factories\ManagementAccountFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManagementAccount extends Model
{
    /** @use HasFactory<ManagementAccountFactory> */
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
     * @param  Builder<ManagementAccount>  $query
     * @return Builder<ManagementAccount>
     */
    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function monthlyAmounts(): HasMany
    {
        return $this->hasMany(MonthlyAmount::class);
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
