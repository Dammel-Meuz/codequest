
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercise_test_cases', function (Blueprint $table) {
            $table->id();

            $table->foreignId('exercise_id')
                ->constrained()
                ->cascadeOnDelete();

            // Entrée transmise au programme JavaScript
            $table->json('input_data');

            // Résultat attendu
            $table->json('expected_output');

            // Les tests cachés ne sont jamais envoyés au navigateur
            $table->boolean('is_hidden')->default(false);

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['exercise_id', 'is_hidden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_test_cases');
    }
};
