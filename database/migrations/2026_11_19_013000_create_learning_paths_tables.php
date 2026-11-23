<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_paths', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('learning_path_chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_path_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['learning_path_id', 'slug']);
        });

        Schema::table('exercises', function (Blueprint $table) {
            $table->foreignId('learning_path_chapter_id')
                ->nullable()
                ->constrained('learning_path_chapters')
                ->nullOnDelete();
        });

        Schema::create('exercise_prerequisites', function (Blueprint $table) {
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prerequisite_exercise_id')->constrained('exercises')->cascadeOnDelete();
            $table->primary(['exercise_id', 'prerequisite_exercise_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_prerequisites');

        Schema::table('exercises', function (Blueprint $table) {
            $table->dropConstrainedForeignId('learning_path_chapter_id');
        });

        Schema::dropIfExists('learning_path_chapters');
        Schema::dropIfExists('learning_paths');
    }
};
