<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debates', function (Blueprint $table) {
            $table->id();
            $table->string('topic');
            $table->string('status')->default('pending'); // pending, running, finished
            $table->json('agents'); // Store agent configs
            $table->json('arguments')->nullable(); // Store debate arguments
            $table->string('winner')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debates');
    }
};