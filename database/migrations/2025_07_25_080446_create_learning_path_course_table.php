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
        Schema::create('learning_path_course', function (Blueprint $table) {
            $table->unsignedBigInteger('learning_path_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedSmallInteger('order_index');
            
            // Set composite primary key
            $table->primary(['learning_path_id', 'course_id']);
            
            // Add foreign key constraints with index
            $table->foreign('learning_path_id')
                  ->references('id')
                  ->on('learning_paths')
                  ->onDelete('cascade');
            $table->index('learning_path_id');
            
            $table->foreign('course_id')
                  ->references('id')
                  ->on('courses')
                  ->onDelete('cascade');
            $table->index('course_id');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_path_course');
    }
};
