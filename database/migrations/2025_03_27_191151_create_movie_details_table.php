<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movie_details', function (Blueprint $table) {
            $table->id();
            $table->integer('tmdb_id')->unique();
            $table->json('data');
            $table->timestamps();
            
            // Add index for faster lookups
            $table->index('tmdb_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movie_details');
    }
};