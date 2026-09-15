<?php

namespace Database\Factories;

use App\Enums\JournalSide;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\LedgerAccount;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<JournalEntryLine> */
class JournalEntryLineFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'journal_entry_id' => function (array $attributes): int {
                return JournalEntry::factory()->create(['organization_id' => $attributes['organization_id']])->id;
            },
            'line_number' => fake()->unique()->numberBetween(1, 100),
            'ledger_account_id' => function (array $attributes): int {
                return LedgerAccount::factory()->create(['organization_id' => $attributes['organization_id']])->id;
            },
            'department_id' => null,
            'side' => fake()->randomElement(JournalSide::cases()),
            'amount' => fake()->randomFloat(2, 1, 1000000),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
