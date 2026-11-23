<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_path_chapters', function (Blueprint $table) {
            $table->longText('course_content')->nullable();
        });

        Schema::create('chapter_quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_path_chapter_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('prompt');
            $table->json('options');
            $table->string('correct_option', 1);
            $table->text('explanation')->nullable();
            $table->unsignedInteger('xp_reward')->default(5);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['learning_path_chapter_id', 'sort_order']);
        });

        Schema::create('chapter_quiz_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chapter_quiz_question_id')
                ->constrained('chapter_quiz_questions')
                ->restrictOnDelete();
            $table->string('selected_option', 1);
            $table->unsignedInteger('xp_earned');
            $table->timestamp('completed_at');
            $table->timestamps();
            $table->unique(['user_id', 'chapter_quiz_question_id'], 'unique_user_quiz_completion');
        });

        Schema::table('xp_transactions', function (Blueprint $table) {
            $table->foreignId('chapter_quiz_completion_id')
                ->nullable()
                ->unique()
                ->constrained('chapter_quiz_completions')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('xp_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('chapter_quiz_completion_id');
        });

        Schema::dropIfExists('chapter_quiz_completions');
        Schema::dropIfExists('chapter_quiz_questions');

        Schema::table('learning_path_chapters', function (Blueprint $table) {
            $table->dropColumn('course_content');
        });
    }
};
