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
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('correlativo')->unique();
            $table->string('external_id')->nullable()->unique();
            $table->string('home_team');
            $table->string('away_team');
            $table->string('home_team_flag')->nullable();
            $table->string('away_team_flag')->nullable();
            $table->unsignedTinyInteger('home_score')->nullable();
            $table->unsignedTinyInteger('away_score')->nullable();
            $table->unsignedTinyInteger('home_score_ht')->nullable();
            $table->unsignedTinyInteger('away_score_ht')->nullable();
            $table->dateTime('match_date');
            $table->string('stage')->default('group');
            $table->string('group_name')->nullable();
            $table->string('venue')->nullable();
            $table->string('status')->default('scheduled');
            $table->unsignedSmallInteger('minute')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('match_date');
            $table->index('stage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
