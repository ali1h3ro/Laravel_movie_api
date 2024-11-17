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
        Schema::table('movies', function (Blueprint $table) {
            // Add external_id for the TMDB unique identifier
            $table->string('external_id')->unique()->nullable(false); // Non-nullable, should be unique

            // Add fetch_logs to store logs as JSON
            $table->json('fetch_logs')->nullable(); // Store the fetch logs
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            // Drop the columns if migrating down
            $table->dropColumn('external_id');
            $table->dropColumn('fetch_logs');
        });
    }
};
