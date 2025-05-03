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
        // Create movie_details table
        Schema::create('movie_details', function (Blueprint $table) {
            $table->id();
            $table->integer('tmdb_id')->unique();
            $table->json('data');
            $table->timestamps();
        });

        // Create search_results table
        Schema::create('search_results', function (Blueprint $table) {
            $table->id();
            $table->string('query');
            $table->integer('page');
            $table->json('results');
            $table->timestamps();
            
            $table->unique(['query', 'page']);
        });

        // Create api_configurations table
        Schema::create('api_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('service');
            $table->string('api_key');
            $table->string('proxy')->nullable();
            $table->integer('rate_limit')->default(40);
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->unique(['service', 'api_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movie_details');
        Schema::dropIfExists('search_results');
        Schema::dropIfExists('api_configurations');
    }
};