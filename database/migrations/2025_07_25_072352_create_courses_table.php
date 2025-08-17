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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('banner');
            $table->text('description');
            $table->string('tags')->nullable()->after('description');
            $table->integer('estimated_time')->nullable()->after('tags');
            $table->tinyInteger('is_published')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index('title');
            $table->index('is_published');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
