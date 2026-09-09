<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_type', 32)->default('user')->change();
        });

        DB::table('users')
            ->where('user_type', 'member')
            ->update(['user_type' => 'user']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_type', 32)->default('member')->change();
        });

        DB::table('users')
            ->where('user_type', 'user')
            ->update(['user_type' => 'member']);
    }
};
