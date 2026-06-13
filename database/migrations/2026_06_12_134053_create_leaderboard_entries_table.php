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
        Schema::create('leaderboard_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('total_points')->default(0);
            $table->unsignedSmallInteger('exact_score_count')->default(0);
            $table->unsignedSmallInteger('correct_winner_count')->default(0);
            $table->unsignedSmallInteger('near_prediction_count')->default(0);
            $table->unsignedSmallInteger('matches_predicted')->default(0);
            $table->unsignedSmallInteger('rank')->default(0);
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->unique('participant_id');
            $table->index('rank');
            $table->index('total_points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaderboard_entries');
    }
};
