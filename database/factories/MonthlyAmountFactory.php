<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Department;
use App\Models\MonthlyAmount;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonthlyAmount>
 */
class MonthlyAmountFactory extends Factory
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
            'account_id' => function (array $attributes): int {
                return Account::factory()->create([
                    'organization_id' => $attributes['organization_id'],
                ])->id;
            },
            'period' => fake()->dateTimeBetween('-1 year', '+1 year')->format('Y-m-01'),
            'type' => fake()->randomElement(array_keys(MonthlyAmount::TYPES)),
            'amount' => fake()->randomFloat(2, -1000000, 1000000),
            'memo' => fake()->optional()->sentence(),
            'source' => 'manual',
        ];
    }
}
