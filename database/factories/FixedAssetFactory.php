<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\FixedAsset;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FixedAsset>
 */
class FixedAssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $assetCategory = fake()->randomElement(array_keys(FixedAsset::ASSET_CATEGORIES));

        return [
            'organization_id' => Organization::factory(),
            'department_id' => function (array $attributes): int {
                return Department::factory()->create([
                    'organization_id' => $attributes['organization_id'],
                ])->id;
            },
            'asset_code' => strtoupper(fake()->unique()->bothify('FA-####??')),
            'asset_name' => fake()->words(3, true),
            'asset_category' => $assetCategory,
            'asset_category_detail' => $assetCategory === 'other' ? fake()->words(2, true) : null,
            'acquisition_date' => fake()->dateTimeBetween('-10 years', '-1 month')->format('Y-m-d'),
            'service_start_date' => fn (array $attributes): string => $attributes['acquisition_date'],
            'acquisition_cost' => fake()->randomFloat(2, 100000, 10000000),
            'useful_life_years' => fake()->numberBetween(2, 20),
            'depreciation_method' => in_array($assetCategory, FixedAsset::NON_DEPRECIABLE_CATEGORIES, true)
                ? 'not_applicable'
                : fake()->randomElement(array_keys(FixedAsset::DEPRECIATION_METHODS)),
            'residual_value' => 0,
            'current_period_depreciation_expense' => 0,
            'accumulated_depreciation' => 0,
            'status' => 'held',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
