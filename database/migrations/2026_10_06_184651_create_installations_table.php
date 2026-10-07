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
        Schema::create('installations', function (Blueprint $table) {
            $table->id();
            $table->char('token_hash', 64)->unique();
            $table->string('app_version', 32);
            $table->string('app_build', 32);
            $table->string('macos_version', 16);
            $table->string('architecture', 16);
            $table->unsignedInteger('report_count')->default(1);
            $table->unsignedInteger('upgrade_count')->default(0);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at')->index();
            $table->timestamp('last_reported_at');
            $table->timestamps();
        });
    }
};
