<?php

namespace Database\Factories;

use App\Enums\JournalSide;
use App\Enums\LedgerAccountType;
use App\Models\LedgerAccount;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LedgerAccount> */
class LedgerAccountFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $accountType = fake()->randomElement(LedgerAccountType::cases());

        return [
            'organization_id' => Organization::factory(),
            'code' => strtoupper(fake()->unique()->bothify('A####??')),
            'name' => fake()->words(2, true),
            'account_type' => $accountType,
            'normal_balance' => in_array($accountType, [LedgerAccountType::Asset, LedgerAccountType::Expense], true)
                ? JournalSide::Debit
                : JournalSide::Credit,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
