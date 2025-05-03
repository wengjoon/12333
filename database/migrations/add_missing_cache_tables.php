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
        // Only create search_results table if it doesn't exist
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
        
        // Let's also add indexes to the existing movie_details table if needed
        if (Schema::hasTable('movie_details') && !Schema::hasIndex('movie_details', 'movie_details_tmdb_id_index')) {
            Schema::table('movie_details', function (Blueprint $table) {
                $table->index('tmdb_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_results');
        
        // Remove the index we added if it exists
        if (Schema::hasTable('movie_details') && Schema::hasIndex('movie_details', 'movie_details_tmdb_id_index')) {
            Schema::table('movie_details', function (Blueprint $table) {
                $table->dropIndex('movie_details_tmdb_id_index');
            });
        }
    }
};