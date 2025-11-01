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
        Schema::table('courses', function (Blueprint $table) {
            // Add created_by column as foreign key to users table
            $table->foreignId('created_by')->nullable()->after('is_published')->constrained('users')->onDelete('set null');
            
            // Add index for better query performance
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Drop foreign key constraint and column
            $table->dropForeign(['created_by']);
            $table->dropIndex(['created_by']);
            $table->dropColumn('created_by');
        });
    }
};
