<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('service');
            $table->string('api_key');
            $table->string('proxy')->nullable();
            $table->integer('rate_limit')->default(40);
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            // Add unique index for service and api key
            $table->unique(['service', 'api_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_configurations');
    }
};