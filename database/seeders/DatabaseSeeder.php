<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application using the assumptions in docs/DatabaseSeeder_仮定法人.md.
     */
    public function run(): void
    {
        $this->call([
            InitialTenantSeeder::class,
            DepartmentSeeder::class,
            BudgetActualAccountSeeder::class,
            LedgerAccountSeeder::class,
            JournalEntrySeeder::class,
            BudgetActualEntrySeeder::class,
            FixedAssetSeeder::class,
        ]);
    }
}
