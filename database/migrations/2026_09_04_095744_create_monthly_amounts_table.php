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
        Schema::create('monthly_amounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('department_id');
            $table->foreignId('management_account_id');
            $table->date('period');
            $table->string('type', 16);
            $table->decimal('amount', 15, 2);
            $table->string('memo', 500)->nullable();
            $table->string('source', 32)->default('manual');
            $table->timestamps();

            $table->foreign(['organization_id', 'department_id'])
                ->references(['organization_id', 'id'])
                ->on('departments')
                ->restrictOnDelete();
            $table->foreign(['organization_id', 'management_account_id'])
                ->references(['organization_id', 'id'])
                ->on('management_accounts')
                ->restrictOnDelete();
            $table->index(['organization_id', 'period', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_amounts');
    }
};
