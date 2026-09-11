<?php

namespace App\Models;

use Database\Factories\FixedAssetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedAsset extends Model
{
    /** @use HasFactory<FixedAssetFactory> */
    use HasFactory;

    /** @var array<string, string> */
    public const ASSET_CATEGORIES = [
        'building' => '建物',
        'building_equipment' => '建物附属設備',
        'structure' => '構築物',
        'machinery_equipment' => '機械及び装置',
        'ship' => '船舶',
        'aircraft' => '航空機',
        'vehicle_transportation' => '車両及び運搬具',
        'tool' => '工具',
        'furniture_fixture' => '器具及び備品',
        'software' => 'ソフトウェア',
        'other_intangible' => 'その他の無形固定資産',
        'lease_asset' => 'リース資産',
        'biological_asset' => '生物',
        'land' => '土地',
        'construction_in_progress' => '建設仮勘定',
        'other' => 'その他',
    ];

    /** @var list<string> */
    public const NON_DEPRECIABLE_CATEGORIES = [
        'land',
        'construction_in_progress',
    ];

    /** @var array<string, string> */
    public const DEPRECIATION_METHODS = [
        'straight_line' => '定額法',
        'declining_balance_old' => '定率法（旧）',
        'declining_balance_200' => '200%定率法（新定率法）',
        'units_of_production' => '生産高比例法',
        'sum_of_years_digits' => '級数法',
        'not_applicable' => '償却対象外',
        'other' => 'その他',
    ];

    /** @var array<string, string> */
    public const STATUSES = [
        'held' => '保有中',
        'sold' => '売却',
        'retired' => '除却',
        'other' => 'その他',
    ];

    /** @var list<string> */
    protected $fillable = [
        'department_id',
        'asset_code',
        'asset_name',
        'asset_category',
        'asset_category_detail',
        'acquisition_date',
        'service_start_date',
        'acquisition_cost',
        'useful_life_years',
        'depreciation_method',
        'residual_value',
        'current_period_depreciation_expense',
        'accumulated_depreciation',
        'status',
        'notes',
    ];

    protected $attributes = [
        'residual_value' => '0',
        'current_period_depreciation_expense' => '0',
        'accumulated_depreciation' => '0',
        'status' => 'held',
    ];

    /**
     * @param  Builder<FixedAsset>  $query
     * @return Builder<FixedAsset>
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

    public function bookValue(): string
    {
        $bookValueInCents = $this->amountToCents($this->acquisition_cost)
            - $this->amountToCents($this->accumulated_depreciation);

        return sprintf('%d.%02d', intdiv($bookValueInCents, 100), $bookValueInCents % 100);
    }

    public function assetCategoryLabel(): string
    {
        $category = self::ASSET_CATEGORIES[$this->asset_category] ?? $this->asset_category;

        if ($this->asset_category === 'other' && filled($this->asset_category_detail)) {
            return $category.'（'.$this->asset_category_detail.'）';
        }

        return $category;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'acquisition_date' => 'date',
            'service_start_date' => 'date',
            'acquisition_cost' => 'decimal:2',
            'useful_life_years' => 'integer',
            'residual_value' => 'decimal:2',
            'current_period_depreciation_expense' => 'decimal:2',
            'accumulated_depreciation' => 'decimal:2',
        ];
    }

    private function amountToCents(string $amount): int
    {
        [$integerPart, $decimalPart] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $integerPart * 100) + (int) str_pad($decimalPart, 2, '0');
    }
}
