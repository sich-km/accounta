<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    public const DEFAULT_CODE = 'km';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'fiscal_year_start_month',
    ];

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function departments(): HasManyThrough
    {
        return $this->hasManyThrough(Department::class, Organization::class);
    }

    public function accounts(): HasManyThrough
    {
        return $this->hasManyThrough(Account::class, Organization::class);
    }

    public function monthlyAmounts(): HasManyThrough
    {
        return $this->hasManyThrough(MonthlyAmount::class, Organization::class);
    }
}
