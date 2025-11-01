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
        Schema::create('course_teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            // Ensure unique course-teacher combinations
            $table->unique(['course_id', 'teacher_id']);

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Add indexes for better query performance
            $table->index('course_id');
            $table->index('teacher_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_teachers');
    }
};
