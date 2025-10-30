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
        Schema::create('task_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->string('question_type')->default('single_choice'); // 'single_choice','multiple_choice','description'
            $table->string('question_title');
            $table->text('description');
            $table->jsonb('choices')->default('{}'); // for choices, limits, file rules, etc.
            $table->jsonb('answer_key')->default('{}'); // e.g. correct choice(s)
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_questions');
    }
};
