<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable();
            $table->string('login_id', 50)->nullable();
            $table->string('name', 100)->change();
            $table->string('email')->nullable()->change();
        });

        DB::table('users')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->each(function (object $user): void {
                $organizationId = DB::table('organizations')->insertGetId([
                    'name' => "{$user->name}の組織",
                    'type' => 'company',
                    'fiscal_year_start_month' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'organization_id' => $organizationId,
                        'login_id' => "user{$user->id}",
                    ]);
            });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable(false)->change();
            $table->string('login_id', 50)->nullable(false)->unique()->change();
            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')
            ->whereNull('email')
            ->orderBy('id')
            ->get(['id'])
            ->each(function (object $user): void {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['email' => "rollback-{$user->id}@invalid.local"]);
            });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropUnique(['login_id']);
            $table->dropColumn(['organization_id', 'login_id']);
            $table->string('name')->change();
            $table->string('email')->nullable(false)->change();
        });
    }
};
