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
        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('learning_path_id')->nullable()->after('course_id')->constrained('learning_paths')->onDelete('cascade');
            $table->index('learning_path_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['learning_path_id']);
            $table->dropIndex(['learning_path_id']);
            $table->dropColumn('learning_path_id');
        });
    }
};
