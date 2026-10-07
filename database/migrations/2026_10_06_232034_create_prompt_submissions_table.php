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
        Schema::create('prompt_submissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('submission_id')->unique();
            $table->char('reference', 26)->unique();
            $table->text('prompt');
            $table->string('name', 60)->nullable();
            $table->string('email', 254)->nullable();
            $table->string('app_version', 32);
            $table->string('app_build', 32);
            $table->timestamps();
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prompt_submissions');
    }
};
