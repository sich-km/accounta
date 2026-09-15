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
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->foreignId('originating_department_id')
                ->nullable()
                ->after('organization_id');

            $table->foreign(['organization_id', 'originating_department_id'])
                ->references(['organization_id', 'id'])
                ->on('departments')
                ->restrictOnDelete();
        });

        $organizationDepartments = DB::table('users')
            ->whereNotNull('department_id')
            ->select(['organization_id', 'department_id'])
            ->distinct()
            ->get()
            ->groupBy('organization_id');

        foreach ($organizationDepartments as $organizationId => $departments) {
            if ($departments->count() === 1) {
                DB::table('journal_entries')
                    ->where('organization_id', $organizationId)
                    ->whereNull('originating_department_id')
                    ->update(['originating_department_id' => $departments->first()->department_id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropForeign(['organization_id', 'originating_department_id']);
            $table->dropColumn('originating_department_id');
        });
    }
};
