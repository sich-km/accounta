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
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id');
            $table->string('asset_code', 32);
            $table->string('asset_name', 100);
            $table->string('asset_category', 100);
            $table->string('asset_category_detail', 100)->nullable();
            $table->date('acquisition_date');
            $table->date('service_start_date')->nullable();
            $table->decimal('acquisition_cost', 15, 2);
            $table->unsignedSmallInteger('useful_life_years')->nullable();
            $table->string('depreciation_method', 32);
            $table->decimal('residual_value', 15, 2)->default(0);
            $table->decimal('current_period_depreciation_expense', 15, 2)->default(0);
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->string('status', 32)->default('held');
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'asset_code']);
            $table->unique(['organization_id', 'id']);
            $table->foreign(['organization_id', 'department_id'])
                ->references(['organization_id', 'id'])
                ->on('departments')
                ->restrictOnDelete();
            $table->index(['organization_id', 'status', 'asset_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};
