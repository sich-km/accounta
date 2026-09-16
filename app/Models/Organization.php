<?php

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    /**
     * @var array<string, string>
     */
    public const TYPES = [
        'company' => '法人',
        'sole_proprietor' => '個人事業主',
        'learning' => '学習用',
        'organization' => 'その他の組織',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'type',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function budgetActualAccounts(): HasMany
    {
        return $this->hasMany(BudgetActualAccount::class);
    }

    public function budgetActualEntries(): HasMany
    {
        return $this->hasMany(BudgetActualEntry::class);
    }

    public function fixedAssets(): HasMany
    {
        return $this->hasMany(FixedAsset::class);
    }

    public function ledgerAccounts(): HasMany
    {
        return $this->hasMany(LedgerAccount::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function journalDocuments(): HasMany
    {
        return $this->hasMany(JournalDocument::class);
    }
}
