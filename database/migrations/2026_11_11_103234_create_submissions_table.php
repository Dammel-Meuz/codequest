
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('exercise_id')
                ->constrained()
                ->cascadeOnDelete();

            // Code JavaScript envoyé par l'étudiant
            $table->longText('source_code');

            // pending, processing, accepted, rejected, error
            $table->string('status')->default('pending');

            // Résultat de l'exécution et informations de correction
            $table->json('result')->nullable();

            // Identifiant du moteur externe d'exécution
            $table->string('execution_id')->nullable();

            // Durée d'exécution en millisecondes
            $table->unsignedInteger('execution_time_ms')->nullable();

            // Nombre de tests réussis
            $table->unsignedInteger('passed_tests')->default(0);

            // Nombre total de tests
            $table->unsignedInteger('total_tests')->default(0);

            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'exercise_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
