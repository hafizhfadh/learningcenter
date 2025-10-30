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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();

            $table->string('lesson_type')->default('video'); // 'video','pages','quiz'
            $table->string('lesson_banner')->default('');
            $table->string('lesson_video')->default('');
            $table->boolean('is_published')->default(false);

            $table->string('title');
            $table->string('slug')->unique(); // this will be used for the url and will content the order_index of the lesson
            $table->text('content_body');

            $table->integer('order_index')->default(0);

            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('lesson_section_id')->constrained()->onDelete('cascade');
            
            $table->timestamps();
            $table->softDeletes();

            $table->index(['course_id', 'order_index']); // Index for efficient course lesson ordering queries
            $table->index(['lesson_type', 'lesson_section_id']); // Index for filtering by lesson type
            $table->index(['slug']); // Index for faster URL lookups
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
