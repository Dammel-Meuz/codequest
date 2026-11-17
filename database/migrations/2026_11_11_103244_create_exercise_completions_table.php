
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercise_completions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('exercise_id')
                ->constrained()
                ->cascadeOnDelete();

            // Première soumission ayant réussi
            $table->foreignId('submission_id')
                ->constrained('submissions')
                ->restrictOnDelete();

            $table->unsignedInteger('xp_earned');

            $table->timestamp('completed_at');

            $table->timestamps();

            // Un étudiant ne peut valider qu'une fois un exercice
            $table->unique(
                ['user_id', 'exercise_id'],
                'unique_user_exercise_completion'
            );

            $table->unique('submission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_completions');
    }
};
