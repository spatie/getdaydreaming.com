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
        Schema::create('install_reports', function (Blueprint $table) {
            $table->id();
            $table->char('token_hash', 64);
            $table->date('report_date');
            $table->string('app_version', 32);
            $table->string('app_build', 32);
            $table->string('macos_version', 16);
            $table->string('architecture', 16);
            $table->timestamp('reported_at');
            $table->timestamps();
            $table->unique(['token_hash', 'report_date', 'app_version', 'app_build'], 'install_report_identity');
            $table->index(['token_hash', 'reported_at']);
        });
    }
};
