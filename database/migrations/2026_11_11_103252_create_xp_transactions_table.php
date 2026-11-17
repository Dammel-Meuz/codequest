
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xp_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Soumission ayant déclenché la récompense
            $table->foreignId('submission_id')
                ->nullable()
                ->constrained('submissions')
                ->restrictOnDelete();

            // Quantité de XP gagnée ou retirée
            $table->integer('amount');

            // Motif de la transaction
            $table->string('reason');

            $table->text('description')->nullable();

            $table->timestamps();

            // Une soumission ne peut déclencher qu'une seule récompense
            $table->unique('submission_id');

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xp_transactions');
    }
};
