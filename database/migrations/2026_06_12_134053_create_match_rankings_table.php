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
        Schema::create('match_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prediction_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('rank');
            $table->unsignedSmallInteger('score_diff');
            $table->boolean('is_exact')->default(false);
            $table->boolean('correct_winner')->default(false);
            $table->unsignedSmallInteger('points')->default(0);
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->unique(['match_id', 'participant_id']);
            $table->index(['match_id', 'rank']);
            $table->index(['match_id', 'is_exact']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_rankings');
    }
};
