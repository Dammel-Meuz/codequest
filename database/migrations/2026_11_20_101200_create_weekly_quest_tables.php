<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_quests', function (Blueprint $table) {
            $table->id();
            $table->date('week_start')->unique();
            $table->string('title');
            $table->text('description');
            $table->unsignedInteger('goal_count');
            $table->unsignedInteger('reward_xp');
            $table->timestamps();
        });

        Schema::create('weekly_quest_progresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_quest_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('progress')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['weekly_quest_id', 'user_id']);
        });

        Schema::table('exercise_completions', function (Blueprint $table) {
            $table->index(
                ['user_id', 'completed_at'],
                'exercise_completions_user_completed_idx'
            );
        });

        Schema::table('xp_transactions', function (Blueprint $table) {
            $table->foreignId('weekly_quest_progress_id')
                ->nullable()
                ->unique()
                ->constrained('weekly_quest_progresses')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('xp_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('weekly_quest_progress_id');
        });

        Schema::table('exercise_completions', function (Blueprint $table) {
            $table->dropIndex('exercise_completions_user_completed_idx');
        });

        Schema::dropIfExists('weekly_quest_progresses');
        Schema::dropIfExists('weekly_quests');
    }
};
