<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

    public function down(): void
    {
        Schema::dropIfExists('search_results');
    }
};