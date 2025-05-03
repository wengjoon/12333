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
        // Create movie_details table if it doesn't exist
        if (!Schema::hasTable('movie_details')) {
            Schema::create('movie_details', function (Blueprint $table) {
                $table->id();
                $table->string('tmdb_id')->unique();
                $table->json('data');
                $table->timestamps();
                
                // Add index for faster lookups
                $table->index('tmdb_id');
            });
        }

        // Create search_results table if it doesn't exist
        if (!Schema::hasTable('search_results')) {
            Schema::create('search_results', function (Blueprint $table) {
                $table->id();
                $table->string('query');
                $table->integer('page');
                $table->json('results');
                $table->timestamps();
                
                // Add unique index for query and page
                $table->unique(['query', 'page']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movie_details');
        Schema::dropIfExists('search_results');
    }
};