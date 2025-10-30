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
        // Indexes for lessons table
        Schema::table('lessons', function (Blueprint $table) {
            $table->index(['lesson_section_id', 'order_index'], 'lessons_section_order_idx');
            $table->index(['is_published', 'order_index'], 'lessons_published_order_idx');
            $table->index('slug', 'lessons_slug_idx');
        });

        // Indexes for lesson_sections table
        Schema::table('lesson_sections', function (Blueprint $table) {
            $table->index(['course_id', 'order_index'], 'lesson_sections_course_order_idx');
        });

        // Indexes for progress_logs table
        Schema::table('progress_logs', function (Blueprint $table) {
            $table->index(['user_id', 'course_id'], 'progress_logs_user_course_idx');
            $table->index(['user_id', 'lesson_id'], 'progress_logs_user_lesson_idx');
            $table->index(['course_id', 'action'], 'progress_logs_course_action_idx');
            $table->index('action', 'progress_logs_action_idx');
        });

        // Indexes for enrollments table (already has indexes in the original migration)
        Schema::table('enrollments', function (Blueprint $table) {
            // Note: enrollments table already has indexes for enrollment_status, progress, enrolled_at
            // We'll add composite indexes for better query performance
            $table->index(['user_id', 'course_id'], 'enrollments_user_course_idx');
            $table->index(['course_id', 'enrollment_status'], 'enrollments_course_status_idx');
        });

        // Indexes for courses table
        Schema::table('courses', function (Blueprint $table) {
            $table->index('slug', 'courses_slug_idx');
            $table->index('is_published', 'courses_published_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropIndex('lessons_section_order_idx');
            $table->dropIndex('lessons_published_order_idx');
            $table->dropIndex('lessons_slug_idx');
        });

        Schema::table('lesson_sections', function (Blueprint $table) {
            $table->dropIndex('lesson_sections_course_order_idx');
        });

        Schema::table('progress_logs', function (Blueprint $table) {
            $table->dropIndex('progress_logs_user_course_idx');
            $table->dropIndex('progress_logs_user_lesson_idx');
            $table->dropIndex('progress_logs_course_action_idx');
            $table->dropIndex('progress_logs_action_idx');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex('enrollments_user_course_idx');
            $table->dropIndex('enrollments_course_status_idx');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex('courses_slug_idx');
            $table->dropIndex('courses_published_idx');
        });
    }
};