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
        Schema::create('journal_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id');
            $table->foreignId('journal_entry_id');
            $table->string('disk', 32)->default('local');
            $table->string('path', 500);
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->timestamps();

            $table->foreign(['organization_id', 'journal_entry_id'])
                ->references(['organization_id', 'id'])
                ->on('journal_entries')
                ->cascadeOnDelete();
            $table->index(['organization_id', 'journal_entry_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_documents');
    }
};
