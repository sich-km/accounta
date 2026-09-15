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
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id');
            $table->foreignId('journal_entry_id');
            $table->unsignedSmallInteger('line_number');
            $table->foreignId('ledger_account_id');
            $table->foreignId('department_id')->nullable();
            $table->string('side', 8);
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign(['organization_id', 'journal_entry_id'])
                ->references(['organization_id', 'id'])
                ->on('journal_entries')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'ledger_account_id'])
                ->references(['organization_id', 'id'])
                ->on('ledger_accounts')
                ->restrictOnDelete();
            $table->foreign(['organization_id', 'department_id'])
                ->references(['organization_id', 'id'])
                ->on('departments')
                ->restrictOnDelete();
            $table->unique(['journal_entry_id', 'line_number']);
            $table->index(['organization_id', 'ledger_account_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
    }
};
