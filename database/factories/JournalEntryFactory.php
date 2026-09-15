<?php

namespace Database\Factories;

use App\Models\JournalEntry;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<JournalEntry> */
class JournalEntryFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'originating_department_id' => null,
            'entry_date' => fake()->date(),
            'description' => fake()->sentence(4),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
