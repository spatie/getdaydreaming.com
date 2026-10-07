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
        Schema::create('release_artifacts', function (Blueprint $table) {
            $table->id();
            $table->string('filename', 128)->unique();
            $table->string('url', 2048);
            $table->char('sha256', 64);
            $table->unsignedBigInteger('size');
            $table->string('version', 32);
            $table->unsignedBigInteger('build');
            $table->timestamps();
            $table->index(['version', 'build']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('release_artifacts');
    }
};
