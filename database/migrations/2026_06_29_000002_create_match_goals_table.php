<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->enum('team', ['home', 'away']);
            $table->string('scorer');
            $table->string('minute');        // Display string: "27'", "90+5'", "45'+5'"
            $table->unsignedTinyInteger('minute_value'); // Base minute for calculations
            $table->boolean('is_penalty')->default(false);
            $table->boolean('is_own_goal')->default(false);
            $table->boolean('is_extra_time')->default(false); // minute_value >= 91
            $table->unsignedSmallInteger('sort_order')->default(0); // order within the match
            $table->timestamps();

            $table->index('match_id');
            $table->index(['match_id', 'team']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_goals');
    }
};
