<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('management_accounts', 'budget_actual_accounts');
        Schema::rename('monthly_amounts', 'budget_actual_entries');

        Schema::table('budget_actual_entries', function (Blueprint $table) {
            $table->renameColumn('management_account_id', 'budget_actual_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_actual_entries', function (Blueprint $table) {
            $table->renameColumn('budget_actual_account_id', 'management_account_id');
        });

        Schema::rename('budget_actual_entries', 'monthly_amounts');
        Schema::rename('budget_actual_accounts', 'management_accounts');
    }
};
