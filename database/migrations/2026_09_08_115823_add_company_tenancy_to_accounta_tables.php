<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name', 100);
            $table->unsignedTinyInteger('fiscal_year_start_month')->default(1);
            $table->timestamps();
        });

        $companyId = null;

        if (DB::table('organizations')->exists()) {
            $fiscalYearStartMonth = (int) (DB::table('organizations')
                ->orderBy('id')
                ->value('fiscal_year_start_month') ?? 1);

            $companyId = DB::table('companies')->insertGetId([
                'code' => 'km',
                'name' => 'KM',
                'fiscal_year_start_month' => $fiscalYearStartMonth,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('organizations', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id');
        });

        if ($companyId !== null) {
            DB::table('organizations')->update(['company_id' => $companyId]);
        }

        Schema::table('organizations', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
            $table->foreign('company_id', 'organizations_company_foreign')
                ->references('id')
                ->on('companies')
                ->restrictOnDelete();
            $table->unique(['company_id', 'id'], 'organizations_company_id_unique');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id');
            $table->string('user_type', 32)->default('member')->after('login_id');
        });

        if ($companyId !== null) {
            DB::table('users')->update(['company_id' => $companyId]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
            $table->foreign('company_id', 'users_company_foreign')
                ->references('id')
                ->on('companies')
                ->restrictOnDelete();
            $table->foreign(['company_id', 'organization_id'], 'users_company_organization_foreign')
                ->references(['company_id', 'id'])
                ->on('organizations')
                ->restrictOnDelete();
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('fiscal_year_start_month');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->unsignedTinyInteger('fiscal_year_start_month')->default(1)->after('type');
        });

        DB::table('companies')
            ->orderBy('id')
            ->get(['id', 'fiscal_year_start_month'])
            ->each(function (object $company): void {
                DB::table('organizations')
                    ->where('company_id', $company->id)
                    ->update(['fiscal_year_start_month' => $company->fiscal_year_start_month]);
            });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_company_organization_foreign');
            $table->dropForeign('users_company_foreign');
            $table->dropColumn(['company_id', 'user_type']);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropUnique('organizations_company_id_unique');
            $table->dropForeign('organizations_company_foreign');
            $table->dropColumn('company_id');
        });

        Schema::dropIfExists('companies');
    }
};
