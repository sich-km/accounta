<?php

namespace Database\Factories;

use App\Models\BudgetActualAccount;
use App\Models\BudgetActualEntry;
use App\Models\Department;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetActualEntry>
 */
class BudgetActualEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'department_id' => function (array $attributes): int {
                return Department::factory()->create([
                    'organization_id' => $attributes['organization_id'],
                ])->id;
            },
            'budget_actual_account_id' => function (array $attributes): int {
                return BudgetActualAccount::factory()->create([
                    'organization_id' => $attributes['organization_id'],
                ])->id;
            },
            'period' => fake()->dateTimeBetween('-1 year', '+1 year')->format('Y-m-01'),
            'type' => fake()->randomElement(array_keys(BudgetActualEntry::TYPES)),
            'amount' => fake()->randomFloat(2, -1000000, 1000000),
            'memo' => fake()->optional()->sentence(),
            'source' => 'manual',
        ];
    }
}
