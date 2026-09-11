<?php

namespace Database\Factories;

use App\Models\ManagementAccount;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ManagementAccount>
 */
class ManagementAccountFactory extends Factory
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
            'code' => 'A'.fake()->unique()->numerify('####'),
            'name' => fake()->randomElement(['売上高', '人件費', '外注費', '旅費交通費']),
            'account_type' => fake()->randomElement(array_keys(ManagementAccount::TYPES)),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
