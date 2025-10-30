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
        Schema::create('task_submissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('student_id');
            $table->text('response_text')->nullable();
            $table->string('file_path', 1024)->nullable();
            $table->timestamp('submitted_at');
            $table->unsignedBigInteger('graded_by')->nullable();
            $table->decimal('grade', 5, 2)->nullable(); // e.g. 85.50
            $table->text('feedback_text')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Optional: add foreign keys if you have related tables
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('graded_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_submissions');
    }
};
