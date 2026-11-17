
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description');

            // Niveau de difficulté
            $table->enum('difficulty', [
                'easy',
                'medium',
                'hard',
                'expert'
            ])->default('easy');

            // Récompense en XP
            $table->unsignedInteger('xp_reward');

            // Code de départ proposé à l'étudiant
            $table->longText('starter_code')->nullable();

            // Langage de programmation
            $table->string('language')->default('javascript');

            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['category_id', 'is_published']);
            $table->index('difficulty');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
