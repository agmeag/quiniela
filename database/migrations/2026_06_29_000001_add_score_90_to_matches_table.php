<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            // 90-minute score for knockout matches.
            // Populated automatically from match_goals when AET is detected.
            // Can be overridden manually by admin. Null = use home_score/away_score.
            $table->unsignedTinyInteger('home_score_90')->nullable()->after('away_score');
            $table->unsignedTinyInteger('away_score_90')->nullable()->after('home_score_90');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['home_score_90', 'away_score_90']);
        });
    }
};
