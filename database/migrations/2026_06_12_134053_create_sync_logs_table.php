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
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('full_sync');
            $table->string('status')->default('pending');
            $table->unsignedSmallInteger('matches_fetched')->default(0);
            $table->unsignedSmallInteger('matches_updated')->default(0);
            $table->text('message')->nullable();
            $table->json('meta')->nullable();
            $table->float('duration_seconds')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
